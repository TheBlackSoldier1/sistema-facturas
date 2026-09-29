<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title','Sistema de facturas')</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f3f5f8;color:#172b45;font:15px/1.5 system-ui,sans-serif}header{background:#142b45;color:#fff;padding:22px max(24px,calc((100vw - 1000px)/2))}main{max-width:1000px;margin:auto;padding:30px 24px}h1{margin-top:0}.card{background:#fff;border:1px solid #dce3eb;border-radius:12px;padding:24px;margin-bottom:22px}label{display:block;font-weight:600;margin-bottom:8px}input{width:100%;padding:10px;border:1px solid #9cabbc;border-radius:7px}button,.button{display:inline-block;border:0;border-radius:7px;background:#146c60;color:white;padding:10px 15px;text-decoration:none;cursor:pointer;margin-top:12px}table{width:100%;border-collapse:collapse}td,th{padding:12px;border-bottom:1px solid #e4e9f0;text-align:left}.notice{padding:14px 18px;background:#e1f3e9;color:#155b39;border-radius:8px;margin-bottom:18px}.error{background:#fce9e9;color:#922c2c}.filename{overflow-wrap:anywhere}
</style>
</head>
<body>
<header><strong>Sistema de Facturas · Parte 1</strong></header>
<main>
@if(session('success'))<div class="notice">{{session('success')}}</div>@endif
@if($errors->any())<div class="notice error">@foreach($errors->all() as $error)<div>{{$error}}</div>@endforeach</div>@endif
@yield('content')
</main>
</body>
</html>
