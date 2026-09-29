@extends('layouts.app')
@section('content')
<h1>Facturas por área</h1>
<p>Segunda versión: se incorporan autenticación, roles y áreas.</p>
@if(auth()->user()->isJefe())
<div class="card"><h2>Registrar factura</h2><form method="post" action="{{route('facturas.store')}}" enctype="multipart/form-data">@csrf
<label for="pdf">PDF</label><input type="file" name="pdf" id="pdf" accept=".pdf,application/pdf" required>
<label for="area_id">Área destinataria</label><select name="area_id" id="area_id"><option value="">Sin asignar</option>@foreach(\App\Models\Area::orderBy('nombre')->get() as $area)<option value="{{$area->id}}">{{$area->nombre}}</option>@endforeach</select>
<br><br><button>Registrar factura</button></form></div>
@endif
<div class="card"><h2>Documentos</h2><table><thead><tr><th>ID</th><th>Documento</th><th>Área</th><th>Subido por</th><th>Fecha</th><th></th></tr></thead><tbody>
@forelse($facturas as $f)<tr><td>#{{$f->id}}</td><td class="filename">{{$f->nombre_original}}</td><td>{{$f->area?->nombre ?? 'Sin asignar'}}</td><td>{{$f->uploader?->name ?? '—'}}</td><td>{{$f->created_at->format('d/m/Y H:i')}}</td><td><a class="button" href="{{route('facturas.download',$f)}}">Descargar</a></td></tr>@empty<tr><td colspan="6">No hay facturas disponibles.</td></tr>@endforelse
</tbody></table></div>
@endsection
