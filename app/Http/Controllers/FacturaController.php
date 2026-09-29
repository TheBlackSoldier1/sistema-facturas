<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FacturaController extends Controller
{
    public function index(Request $request)
    {
        $query = Factura::with('area');
        $r = $request;
        $r->validate(['estado' => 'nullable|in:recibida,por_pagar,en_revision,correccion,confirmada', 'q' => 'nullable|string|max:100']);
        if ($r->user()->isJefe() && $r->boolean('papelera')) {
            $query->onlyTrashed();
        }
        if (! $request->user()->isJefe()) {
            abort_unless($request->user()->role === 'usuario' && $request->user()->area_id, 403);
            $query->where('area_id', $request->user()->area_id)->where('estado', '!=', 'recibida');
        }
        if ($r->filled('estado')) {
            $query->where('estado', $r->estado);
        }
        if ($r->filled('q')) {
            $query->where(function ($q) use ($r) {
                $q->where('nombre_original', 'like', '%'.$r->q.'%')->orWhere('proveedor', 'like', '%'.$r->q.'%')->orWhere('folio', 'like', '%'.$r->q.'%');
            });
        }

        return view('facturas.index', ['facturas' => $query->orderByDesc('id')->paginate(10)->withQueryString()]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isJefe(), 403);
        // Se revisa el tipo detectado en el archivo, además de su extensión.
        $request->validate(['pdf' => ['required', 'file', 'mimes:pdf', 'extensions:pdf', 'max:10240']], [
            'pdf.required' => 'Selecciona un PDF antes de guardar.',
            'pdf.file' => 'No se pudo recibir el archivo. Intenta nuevamente.',
            'pdf.mimes' => 'El archivo debe ser un PDF.',
            'pdf.extensions' => 'El archivo debe tener extensión .pdf.',
            'pdf.max' => 'El PDF no puede superar los 10 MB.',
            'pdf.uploaded' => 'No se pudo subir el archivo. Revisa que no supere los 10 MB.',
        ]);
        $archivo = $request->file('pdf');
        $ruta = null;
        try {
            // El nombre aleatorio evita sobrescribir documentos con el mismo nombre.
            $ruta = $archivo->store('facturas', 'local');
            if ($ruta === false) {
                throw new \RuntimeException('No se pudo escribir el PDF.');
            }
            DB::transaction(function () use ($request, $archivo, $ruta) {
                $f = Factura::create([
                    'nombre_original' => mb_substr(basename(str_replace('\\', '/', $archivo->getClientOriginalName())), 0, 255),
                    'ruta_pdf' => $ruta,
                    'tamano_bytes' => $archivo->getSize(),
                    'area_id' => null,
                    'uploaded_by' => $request->user()->id,
                ]);
                $f->refresh();
                $f->documentos()->create(['tipo' => 'factura', 'nombre' => $f->nombre_original, 'ruta' => $ruta, 'bytes' => $f->tamano_bytes, 'user_id' => $request->user()->id]);
                WorkflowController::event($f, $request, 'Factura recibida y registrada', null);
            });
        } catch (Throwable $error) {
            // Si falla el registro, retiramos el archivo para evitar PDFs huérfanos.
            if (is_string($ruta)) {
                Storage::disk('local')->delete($ruta);
            }
            report($error);

            return back()->withErrors(['pdf' => 'No pudimos guardar el PDF. Intenta nuevamente.']);
        }

        return redirect()->route('facturas.index')->with('success', 'PDF guardado correctamente.');
    }

    public function download(Request $request, Factura $factura)
    {
        WorkflowController::access($request, $factura);
        abort_unless(Storage::disk('local')->exists($factura->ruta_pdf), 404, 'El PDF no está disponible.');

        return Storage::disk('local')->download($factura->ruta_pdf, $factura->nombre_original, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
