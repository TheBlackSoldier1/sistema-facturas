@extends('layouts.app')
@section('content')
<h1>{{request()->boolean('papelera') && auth()->user()->isJefe() ? 'Papelera de facturas' : (request('estado')==='confirmada' ? 'Historial de pagos confirmados' : 'Facturas y estados')}}</h1>
<p>{{auth()->user()->isJefe() ? 'Recibe, asigna y revisa cada factura hasta confirmar su pago.' : 'Consulta tus facturas, realiza el pago externamente y envía el comprobante para revisión.'}}</p>
<div class="card"><form class="filters" method="get">
@if(request()->boolean('papelera'))<input type="hidden" name="papelera" value="1">@endif
<div><label for="q">Buscar documento, proveedor o folio</label><input name="q" id="q" value="{{request('q')}}" maxlength="100"></div>
<div><label for="estado">Estado</label><select id="estado" name="estado"><option value="">Todos</option>@foreach(\App\Models\Factura::ESTADOS as $key=>$label)<option value="{{$key}}" @selected(request('estado')===$key)>{{$label}}</option>@endforeach</select></div>
<button>Filtrar</button><a href="{{route('facturas.index')}}">Limpiar</a>
</form></div>
@if(auth()->user()->isJefe() && !request()->boolean('papelera'))
<details class="card"><summary>+ Registrar factura recibida</summary>
<p>Primero guarda el PDF. Luego abre la factura para completar sus datos y enviarla al área correspondiente.</p>
<form method="post" action="{{route('facturas.store')}}" enctype="multipart/form-data">@csrf<label for="pdf">PDF de la factura</label><input type="file" name="pdf" id="pdf" accept=".pdf,application/pdf" required><small>Máximo 10 MB.</small><br><button>Registrar factura</button></form>
</details>
@endif
<div class="card"><h2>Documentos <span class="badge">{{$facturas->total()}}</span></h2>
<div class="tablewrap"><table><thead><tr><th>Factura</th><th>Área</th><th>Estado</th><th>Actualización</th><th>Acceso</th></tr></thead><tbody>
@forelse($facturas as $f)<tr>
<td><strong>#{{$f->id}} · {{$f->proveedor ?: 'Sin proveedor'}}</strong><div class="filename">{{$f->nombre_original}}</div><small>Folio: {{$f->folio ?: 'Sin folio'}}</small></td>
<td>{{$f->area?->nombre ?? 'Sin asignar'}}</td><td><span class="badge {{$f->estado}}">{{\App\Models\Factura::ESTADOS[$f->estado]}}</span>@if($f->trashed())<div class="error">Eliminada</div>@endif</td>
<td>{{$f->updated_at->format('d/m/Y H:i')}}</td><td><a class="button secondary" href="{{route('facturas.show',$f)}}">{{auth()->user()->isJefe()?'Administrar':'Ver factura'}}</a></td>
</tr>@empty<tr><td colspan="5"><div class="empty">No hay facturas para esta selección.</div></td></tr>@endforelse
</tbody></table></div>
@include('facturas.pagination',['page'=>$facturas])
</div>
@endsection

