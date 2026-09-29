@extends('layouts.app')
@section('content')
<h1>Notificaciones</h1><p>Avisos de asignaciones, comprobantes, correcciones y confirmaciones.</p>
@forelse($avisos as $aviso)
<div class="card"><span class="badge">{{$aviso->read_at?'Leída':'Nueva'}}</span><p style="white-space:pre-wrap">{{$aviso->mensaje}}</p><small>{{$aviso->created_at->format('d/m/Y H:i')}}</small><div class="row">
@if($aviso->factura && (auth()->user()->isJefe() || (!$aviso->factura->trashed() && $aviso->factura->estado!=='recibida' && auth()->user()->role==='usuario' && auth()->user()->area_id && (int)$aviso->factura->area_id===(int)auth()->user()->area_id)))
<a class="button secondary" href="{{route('facturas.show',$aviso->factura_id)}}">Ver factura</a>
@if(!$aviso->factura->trashed())
<a class="button outline" href="{{route('facturas.download',$aviso->factura_id)}}">Descargar PDF</a>
@if(!auth()->user()->isJefe() && in_array($aviso->factura->estado,['por_pagar','correccion']))
<a class="button" href="{{route('facturas.show',$aviso->factura_id)}}#subir-comprobante">Subir comprobante</a>
@endif
@endif
@endif
@if(!$aviso->read_at)<form method="post" action="{{route('notifications.read',$aviso)}}">@csrf<button class="secondary">Marcar como leída</button></form>@endif
</div></div>
@empty<div class="card empty">No tienes notificaciones todavía.</div>@endforelse
@include('facturas.pagination',['page'=>$avisos])
@endsection
