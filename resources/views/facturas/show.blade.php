@extends('layouts.app')
@section('content')
@php($chief=auth()->user()->isJefe())
<h1>Factura #{{$factura->id}} · {{$factura->proveedor ?: 'Sin proveedor'}}</h1>
<p>Folio: {{$factura->folio ?: 'Sin folio'}} · Área: {{$factura->area?->nombre ?? 'Sin asignar'}}</p>
<div class="row" style="margin-bottom:24px"><span class="badge {{$factura->estado}}">{{\App\Models\Factura::ESTADOS[$factura->estado]}}</span>@if($factura->trashed())<span class="badge error">Eliminada · Conservada en papelera</span>@endif</div>
<div class="grid">
<section>
<div class="card"><h2>Factura y comprobantes</h2><p>Las versiones anteriores se conservan. El último comprobante enviado es el que se revisa.</p>
@foreach($factura->documentos->sortByDesc('id') as $doc)
<div class="doc"><strong>{{$doc->tipo==='factura'?'Factura':'Comprobante de pago'}} #{{$doc->id}}</strong><div class="filename">{{$doc->nombre}}</div><small>{{$doc->created_at->format('d/m/Y H:i')}} · {{$doc->user?->name ?? 'Registro anterior'}} · {{number_format($doc->bytes/1024,1,',','.')}} KB</small>
<div class="row"><a class="button outline" target="_blank" rel="noopener" href="{{route('facturas.document',[$factura,$doc,'preview'=>1])}}">Vista previa</a><a class="button secondary" href="{{route('facturas.document',[$factura,$doc])}}">Descargar</a></div></div>
@endforeach
</div>
@if(!$chief && !$factura->trashed() && in_array($factura->estado,['por_pagar','correccion']))
<div class="card" id="subir-comprobante"><h2>{{$factura->estado==='correccion'?'Enviar comprobante corregido':'Informar pago realizado'}}</h2>
@if($factura->estado==='correccion')<div class="notice error">{{$factura->eventos->firstWhere('accion','Corrección solicitada')?->detalle}}</div>@endif
<p>Realiza el pago fuera de esta aplicación y adjunta su comprobante PDF. El jefe debe revisarlo antes de confirmarlo.</p>
<form method="post" action="{{route('facturas.upload',$factura)}}" enctype="multipart/form-data">@csrf<input type="hidden" name="version" value="{{$factura->version}}"><label for="receipt">Comprobante PDF</label><input id="receipt" type="file" name="pdf" accept=".pdf,application/pdf" required><small>Máximo 10 MB. No reemplaza la factura original.</small><br><button>Enviar a revisión</button></form>
</div>
@endif
@if(!$chief && $factura->estado==='en_revision')<div class="card readonly">El comprobante está en revisión. Recibirás una notificación cuando el jefe confirme el pago o solicite una corrección.</div>@endif
@if(!$chief && $factura->estado==='confirmada')<div class="card readonly"><strong>Pago confirmado</strong><p>Esta factura forma parte del historial de tu área. Solo puedes consultar, previsualizar y descargar sus documentos.</p></div>@endif
@if($chief && !$factura->trashed())
<div class="card"><h2>Editar datos de la factura</h2><form method="post" action="{{route('facturas.update',$factura)}}">@csrf @method('PATCH')<input type="hidden" name="version" value="{{$factura->version}}">
<label for="proveedor">Proveedor</label><input id="proveedor" name="proveedor" maxlength="255" value="{{old('proveedor',$factura->proveedor)}}">
<label for="folio">Folio</label><input id="folio" name="folio" maxlength="100" value="{{old('folio',$factura->folio)}}"><button>Guardar datos</button></form>
<small>Las ediciones quedan registradas y no cambian la aprobación del pago.</small>
</div>
@if($factura->estado==='recibida')
<details class="card"><summary>Actualizar PDF de la factura</summary><p>Disponible antes del envío al área. Se conserva el PDF anterior.</p><form method="post" action="{{route('facturas.upload',$factura)}}" enctype="multipart/form-data">@csrf<input type="hidden" name="version" value="{{$factura->version}}"><label for="replace">Nuevo PDF</label><input id="replace" type="file" name="pdf" accept=".pdf,application/pdf" required><button>Guardar nueva versión</button></form></details>
@endif
@endif
</section>
<section>
@if($chief)
@if($factura->trashed())
<div class="card"><h2>Restaurar factura</h2><p>Recupera la factura con su estado y documentos anteriores.</p><form method="post" action="{{route('facturas.action',$factura)}}">@csrf<input type="hidden" name="version" value="{{$factura->version}}"><input type="hidden" name="accion" value="restaurar"><button>Restaurar factura</button></form></div>
@elseif(in_array($factura->estado,['recibida','por_pagar']))
<div class="card"><h2>Enviar PDF y notificar</h2><p>Selecciona el área responsable. Los usuarios del área recibirán un aviso interno y un correo individual con el PDF adjunto y las instrucciones para pagar.</p><form method="post" action="{{route('facturas.action',$factura)}}">@csrf<input type="hidden" name="version" value="{{$factura->version}}"><input type="hidden" name="accion" value="asignar"><label for="area">Área destinataria</label><select id="area" name="area_id" required><option value="">Selecciona un área</option>@foreach($areas as $area)<option value="{{$area->id}}" @selected($factura->area_id===$area->id)>{{$area->nombre}}</option>@endforeach</select>
<div id="recipients" aria-live="polite"></div>
<label for="instructions">Mensaje para el área (opcional)</label><textarea id="instructions" name="motivo" maxlength="2000" placeholder="Indica qué deben revisar o adjuntar.">{{old('motivo')}}</textarea><button>Enviar PDF y notificación</button><small style="display:block;margin-top:10px">La factura quedará «Por pagar». Se conserva el aviso interno y se envía correo según MAIL_*. En modo log/array no hay entrega real. Volver a asignar envía otro correo a todos los usuarios del área.</small></form>
<script>
const recipientsByArea = {{ Illuminate\Support\Js::from($destinatarios->map(fn($users)=>$users->map(fn($user)=>['name'=>$user->name,'email'=>$user->email])->values())) }};
const areaSelect=document.getElementById('area'), recipientsBox=document.getElementById('recipients');
function showRecipients(){const users=recipientsByArea[areaSelect.value]||[];recipientsBox.textContent=areaSelect.value?(users.length?'Recibirán el aviso: '+users.map(u=>u.name+' ('+u.email+')').join(', '):'Esta área no tiene usuarios disponibles.'):'Selecciona un área para ver quién recibirá el aviso.';}
areaSelect.addEventListener('change',showRecipients);showRecipients();
</script></div>
@elseif($factura->estado==='en_revision')
<div class="card"><h2>Revisar comprobante</h2><p>Abre el comprobante más reciente antes de decidir. Si existe un error, describe qué debe corregirse.</p><form method="post" action="{{route('facturas.action',$factura)}}">@csrf<input type="hidden" name="version" value="{{$factura->version}}"><label for="motivo">Observación (obligatoria al solicitar corrección)</label><textarea id="motivo" name="motivo" maxlength="2000">{{old('motivo')}}</textarea><div class="row"><button name="accion" value="confirmar">Confirmar pago</button><button class="secondary" name="accion" value="corregir">Solicitar corrección</button></div></form></div>
@elseif($factura->estado==='confirmada')
<div class="card readonly"><strong>Pago confirmado y área notificada.</strong><p>La factura permanece en el historial. Sus comprobantes no se modifican.</p></div>
@else
<div class="card"><h2>Esperando corrección del área</h2><p>La próxima carga del área enviará nuevamente el comprobante a revisión.</p></div>
@endif
@endif
@if($chief)
<div class="card"><h2>Historial de esta factura</h2><div class="timeline">@foreach($factura->eventos as $event)<div class="event"><strong>{{$event->accion}}</strong><div><small>{{$event->created_at->format('d/m/Y H:i:s')}} · {{$event->user?->name ?? 'Sistema'}}</small></div><small>{{\App\Models\Factura::ESTADOS[$event->anterior] ?? 'Inicio'}} → {{\App\Models\Factura::ESTADOS[$event->nuevo]}}</small><p>{{$event->detalle}}</p></div>@endforeach</div></div>
@else
<div class="card"><h2>Consulta de documentos</h2><p>El historial de auditoría completo pertenece al jefe de informática. Aquí puedes consultar los documentos y el estado de esta factura de tu área.</p></div>
@endif
@if($chief && !$factura->trashed())
<details class="card dangerzone"><summary>Eliminar factura</summary><p>La factura se retirará del área y pasará a la papelera. El historial y los PDFs se conservarán.</p><form method="post" action="{{route('facturas.action',$factura)}}">@csrf<input type="hidden" name="version" value="{{$factura->version}}"><input type="hidden" name="accion" value="eliminar"><label for="delete-reason">Motivo</label><textarea id="delete-reason" name="motivo" required minlength="5" maxlength="2000"></textarea><button class="danger">Mover a la papelera</button></form></details>
@endif
</section></div>
@endsection

