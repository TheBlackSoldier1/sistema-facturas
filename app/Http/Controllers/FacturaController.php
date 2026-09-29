<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FacturaController extends Controller
{
    public function index()
    {
        return view('facturas.index', [
            'facturas' => Factura::orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'extensions:pdf', 'max:10240'],
        ], [
            'pdf.required' => 'Selecciona un PDF antes de guardar.',
            'pdf.mimes' => 'El archivo debe ser un PDF.',
            'pdf.extensions' => 'El archivo debe tener extensión .pdf.',
            'pdf.max' => 'El PDF no puede superar los 10 MB.',
        ]);

        $archivo = $request->file('pdf');
        $ruta = null;

        try {
            $ruta = $archivo->store('facturas', 'local');
            if (! $ruta) {
                throw new \RuntimeException('No se pudo guardar el archivo.');
            }

            Factura::create([
                'nombre_original' => mb_substr(basename(str_replace('\\', '/', $archivo->getClientOriginalName())), 0, 255),
                'ruta_pdf' => $ruta,
                'tamano_bytes' => $archivo->getSize(),
            ]);
        } catch (Throwable $error) {
            if (is_string($ruta)) {
                Storage::disk('local')->delete($ruta);
            }

            report($error);
            return back()->withErrors(['pdf' => 'No pudimos guardar el PDF. Intenta nuevamente.']);
        }

        return redirect()->route('facturas.index')->with('success', 'PDF guardado correctamente.');
    }

    public function download(Factura $factura)
    {
        abort_unless(Storage::disk('local')->exists($factura->ruta_pdf), 404, 'El PDF no está disponible.');

        return Storage::disk('local')->download($factura->ruta_pdf, $factura->nombre_original, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
