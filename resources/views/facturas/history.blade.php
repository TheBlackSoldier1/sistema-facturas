@extends('layouts.app')
@section('content')
<h1>Historial completo</h1><p>Auditoría exclusiva del jefe de informática. Incluye las facturas eliminadas y restauradas.</p>
<div class="card"><div class="tablewrap"><table><thead><tr><th>Fecha / actor</th><th>Factura</th><th>Acción / estado</th><th>Detalle</th></tr></thead><tbody>
@forelse($events as $event)<tr><td>{{$event->created_at->format('d/m/Y H:i:s')}}<br><small>{{$event->user?->name ?? 'Sistema'}}</small></td><td><a href="{{route('facturas.show',$event->factura_id)}}">#{{$event->factura_id}} · Administrar</a></td><td><strong>{{$event->accion}}</strong><br><small>{{\App\Models\Factura::ESTADOS[$event->anterior] ?? 'Inicio'}} → {{\App\Models\Factura::ESTADOS[$event->nuevo]}}</small></td><td style="white-space:pre-wrap;overflow-wrap:anywhere">{{$event->detalle}}</td></tr>
@empty<tr><td colspan="4">Aún no hay movimientos.</td></tr>@endforelse
</tbody></table></div>@include('facturas.pagination',['page'=>$events])</div>
@endsection

