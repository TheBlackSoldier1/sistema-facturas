<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Aviso;
use App\Models\Documento;
use App\Models\Evento;
use App\Models\Factura;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class WorkflowController extends Controller
{
    public static function access(Request $r, Factura $f): void
    {
        abort_unless($r->user()->isJefe() || (! $f->trashed() && $f->estado !== 'recibida' && $r->user()->role === 'usuario' && $r->user()->area_id && (int) $r->user()->area_id === (int) $f->area_id), 403);
    }

    public static function event(Factura $f, Request $r, string $action, ?string $before, ?string $detail = null): void
    {
        Evento::create(['factura_id' => $f->id, 'user_id' => $r->user()->id, 'accion' => $action, 'anterior' => $before, 'nuevo' => $f->estado, 'detalle' => $detail]);
    }

    private function notify(Factura $f, string $message, bool $toChief = false): void
    {
        $users = $toChief ? User::where('role', 'jefe')->get() : User::where('role', 'usuario')->where('area_id', $f->area_id)->get();
        foreach ($users as $u) {
            Aviso::create(['user_id' => $u->id, 'factura_id' => $f->id, 'mensaje' => "Factura #{$f->id}: ".$message]);
        }
    }

    public function show(Request $r, Factura $factura)
    {
        self::access($r, $factura);

        return view('facturas.show', ['factura' => $factura->load(['area', 'documentos.user', 'eventos.user']), 'areas' => Area::orderBy('nombre')->get(), 'destinatarios' => $r->user()->isJefe() ? User::where('role', 'usuario')->orderBy('name')->get()->groupBy('area_id') : collect()]);
    }

    public function history(Request $r)
    {
        abort_unless($r->user()->isJefe(), 403);
        $events = Evento::with('user')->orderByDesc('id')->paginate(30);

        return view('facturas.history', compact('events'));
    }

    public function notifications(Request $r)
    {
        $avisos = Aviso::with('factura')->where('user_id', $r->user()->id)->orderByDesc('id')->paginate(20);

        return view('facturas.notifications', compact('avisos'));
    }

    public function read(Request $r, Aviso $aviso)
    {
        abort_unless($aviso->user_id === $r->user()->id, 403);
        $aviso->update(['read_at' => now()]);

        return back();
    }

    public function document(Request $r, Factura $factura, Documento $documento)
    {
        self::access($r, $factura);
        abort_unless($documento->factura_id === $factura->id, 404);
        abort_unless(Storage::disk('local')->exists($documento->ruta), 404);
        $headers = ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];
        if ($r->boolean('preview')) {
            return Storage::disk('local')->response($documento->ruta, $documento->nombre, $headers, 'inline');
        }

        return Storage::disk('local')->download($documento->ruta, $documento->nombre, $headers);
    }

    // La versión impide aplicar formularios obsoletos o confirmar dos veces.
    private function change(Request $r, Factura $factura, callable $operation)
    {
        $r->validate(['version' => 'required|integer|min:0']);
        DB::transaction(function () use ($r, $factura, $operation) {
            $f = Factura::withTrashed()->findOrFail($factura->id);
            self::access($r, $f);
            $changed = Factura::withTrashed()->whereKey($f->id)->where('version', $r->integer('version'))->update(['version' => DB::raw('version + 1')]);
            if (! $changed) {
                throw ValidationException::withMessages(['version' => 'La factura cambió desde que abriste esta página. Recarga antes de continuar.']);
            }
            $f->refresh();
            $operation($f);
        });

        return redirect()->route('facturas.show', $factura->id)->with('success', 'Cambio guardado en el historial.');
    }

    public function action(Request $r, Factura $factura)
    {
        abort_unless($r->user()->isJefe(), 403);
        $r->validate(['accion' => 'required|in:asignar,confirmar,corregir,eliminar,restaurar', 'motivo' => 'nullable|string|max:2000']);

        return $this->change($r, $factura, function ($f) use ($r) {
            $before = $f->estado;
            $action = $r->string('accion')->toString();
            if ($action === 'restaurar') {
                abort_unless($f->trashed(), 422);
                $f->restore();
                self::event($f, $r, 'Restaurada', $before);
                if ($f->area_id) {
                    $this->notify($f, 'La factura fue restaurada.');
                }

                return;
            }
            abort_if($f->trashed(), 422);
            if ($action === 'eliminar') {
                $r->validate(['motivo' => 'required|string|min:5|max:2000'], ['motivo.required' => 'Indica el motivo de eliminación.', 'motivo.min' => 'Explica el motivo con al menos 5 caracteres.']);
                self::event($f, $r, 'Eliminada (recuperable)', $before, $r->motivo);
                $f->delete();
                if ($f->area_id) {
                    $this->notify($f, 'La factura fue retirada por el jefe. Motivo: '.$r->motivo);
                }
            } elseif ($action === 'asignar') {
                abort_unless(in_array($f->estado, ['recibida', 'por_pagar']), 422);
                $r->validate(['area_id' => 'required|exists:areas,id']);
                $area = Area::findOrFail($r->area_id);
                if (! User::where('role', 'usuario')->where('area_id', $area->id)->exists()) {
                    throw ValidationException::withMessages(['area_id' => 'Esta área no tiene usuarios para recibir la factura.']);
                }
                if ($f->area_id && (int) $f->area_id !== (int) $area->id) {
                    $this->notify($f, 'La factura fue reasignada a otra área y dejó de estar disponible.');
                }
                $previousArea = $f->area?->nombre ?? 'Sin área';
                $f->update(['area_id' => $area->id, 'estado' => 'por_pagar']);
                self::event($f, $r, 'Enviada al área', $before, $previousArea.' → '.$area->nombre.($r->motivo ? '. Instrucciones: '.$r->motivo : ''));
                $this->notify($f, 'El jefe te envió el PDF «'.$f->nombre_original.'». Descarga la factura, realiza el pago externamente y sube su comprobante para revisión.'.($r->motivo ? ' Instrucciones: '.$r->motivo : ''));
            } else {
                abort_unless($f->estado === 'en_revision', 422);
                $receipt = $f->documentos()->where('tipo', 'comprobante')->latest('id')->first();
                abort_unless($receipt, 422);
                if ($action === 'corregir') {
                    $r->validate(['motivo' => 'required|string|min:5|max:2000'], ['motivo.required' => 'Explica qué debe corregir el área.', 'motivo.min' => 'Explica la corrección con al menos 5 caracteres.']);
                    $f->update(['estado' => 'correccion']);
                    self::event($f, $r, 'Corrección solicitada', $before, 'Comprobante #'.$receipt->id.'. '.$r->motivo);
                    $this->notify($f, 'Debes corregir el comprobante: '.$r->motivo);
                } else {
                    $f->update(['estado' => 'confirmada']);
                    self::event($f, $r, 'Pago confirmado', $before, 'Comprobante aprobado #'.$receipt->id.($r->motivo ? '. '.$r->motivo : ''));
                    $this->notify($f, 'El jefe confirmó el pago. El documento está disponible en tu historial.');
                }
            }
        });
    }

    public function update(Request $r, Factura $factura)
    {
        abort_unless($r->user()->isJefe(), 403);
        $data = $r->validate(['proveedor' => 'nullable|string|max:255', 'folio' => 'nullable|string|max:100']);

        return $this->change($r, $factura, function ($f) use ($r, $data) {
            abort_if($f->trashed(), 422);
            $detail = 'Proveedor: '.($f->proveedor ?? '—').' → '.($data['proveedor'] ?? '—').'; Folio: '.($f->folio ?? '—').' → '.($data['folio'] ?? '—');
            $f->update($data);
            self::event($f, $r, 'Datos editados', $f->estado, $detail);
        });
    }

    public function upload(Request $r, Factura $factura)
    {
        self::access($r, $factura);
        $r->validate(['version' => 'required|integer|min:0', 'pdf' => ['required', 'file', 'mimes:pdf', 'extensions:pdf', 'max:10240']], [
            'pdf.required' => 'Selecciona un PDF.', 'pdf.mimes' => 'Solo se aceptan PDFs.', 'pdf.extensions' => 'Usa la extensión .pdf.', 'pdf.max' => 'El PDF no puede superar 10 MB.', 'pdf.uploaded' => 'No se pudo subir el PDF. Límite: 10 MB.',
        ]);
        $chief = $r->user()->isJefe();
        abort_unless(! $factura->trashed() && ($chief ? $factura->estado === 'recibida' : in_array($factura->estado, ['por_pagar', 'correccion'])), 422);
        $path = $r->file('pdf')->store('facturas', 'local');
        if (! $path) {
            return back()->withErrors(['pdf' => 'No pudimos guardar el archivo.']);
        }
        try {
            return $this->change($r, $factura, function ($f) use ($r, $path, $chief) {
                abort_unless(! $f->trashed() && ($chief ? $f->estado === 'recibida' : in_array($f->estado, ['por_pagar', 'correccion'])), 422);
                $file = $r->file('pdf');
                $name = mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 255);
                $doc = $f->documentos()->create(['tipo' => $chief ? 'factura' : 'comprobante', 'nombre' => $name, 'ruta' => $path, 'bytes' => $file->getSize(), 'user_id' => $r->user()->id]);
                $before = $f->estado;
                if ($chief) {
                    $f->update(['ruta_pdf' => $path, 'nombre_original' => $name, 'tamano_bytes' => $file->getSize()]);
                    self::event($f, $r, 'PDF de factura actualizado', $before, 'Documento #'.$doc->id.'. Se conserva la versión anterior.');
                } else {
                    $f->update(['estado' => 'en_revision']);
                    self::event($f,$r,'Comprobante enviado',$before,'Documento #'.$doc->id.'. Pago realizado externamente, pendiente de revisión.');
                    $this->notify($f,'Hay un comprobante de pago para revisar.',true);
                }
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
    }
}
