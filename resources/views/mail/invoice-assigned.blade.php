<!doctype html>
<html lang="es"><head><meta charset="utf-8"><title>Factura asignada para pago</title></head><body>
<h1>Factura #{{$factura->id}} · Por pagar</h1>
<p>El jefe asignó esta factura al área {{$areaName}}.</p>
<p>Proveedor: {{$factura->proveedor ?: 'Sin proveedor'}}<br>Folio: {{$factura->folio ?: 'Sin folio'}}<br>Archivo: {{$factura->nombre_original}}</p>
<p>Se adjunta el PDF de la factura. Coordina el pago con los demás usuarios del área para evitar pagos duplicados. Realiza el pago externamente y sube el comprobante PDF al sistema para revisión del jefe.</p>
@if($instructions !== '')<h2>Instrucciones</h2><p style="white-space:pre-wrap">{{$instructions}}</p>@endif
<p><a href="{{rtrim(config('app.url'), '/')}}/facturas/{{$factura->id}}#subir-comprobante">Abrir factura y subir comprobante</a></p>
<p>Debes iniciar sesión con tu cuenta del área. Verifica el estado actual en el sistema antes de pagar.</p>
</body></html>
