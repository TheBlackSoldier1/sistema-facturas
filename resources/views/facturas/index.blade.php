@extends('layouts.app')
@section('content')
<h1>Registro básico de facturas</h1>
<p>Primera versión: permite guardar documentos PDF y descargarlos posteriormente.</p>
<div class="card">
<h2>Guardar factura</h2>
<form method="post" action="{{route('facturas.store')}}" enctype="multipart/form-data">
@csrf
<label for="pdf">Archivo PDF</label>
<input type="file" name="pdf" id="pdf" accept=".pdf,application/pdf" required>
<button>Guardar PDF</button>
</form>
</div>
<div class="card">
<h2>Facturas registradas</h2>
<table><thead><tr><th>ID</th><th>Documento</th><th>Tamaño</th><th>Fecha</th><th></th></tr></thead><tbody>
@forelse($facturas as $factura)
<tr><td>#{{$factura->id}}</td><td class="filename">{{$factura->nombre_original}}</td><td>{{number_format($factura->tamano_bytes / 1024, 1)}} KB</td><td>{{$factura->created_at->format('d/m/Y H:i')}}</td><td><a class="button" href="{{route('facturas.download',$factura)}}">Descargar</a></td></tr>
@empty<tr><td colspan="5">Todavía no hay facturas registradas.</td></tr>@endforelse
</tbody></table>
</div>
@endsection
