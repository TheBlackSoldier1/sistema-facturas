@extends('layouts.app')
@section('title', 'Usuarios · Facturas')
@section('content')
<h1>Gestión de usuarios</h1>
<p>Registra cuentas y envía correos a usuarios existentes. Solo jefe y admin pueden acceder.</p>
<div class="card">
<h2>Registrar usuario</h2>
<form method="post" action="{{route('users.store')}}">
@csrf
<div class="grid"><div>
<label for="name">Nombre</label><input id="name" name="name" value="{{old('name')}}" maxlength="255" autocomplete="name" required>
<label for="email">Correo</label><input id="email" type="email" name="email" value="{{old('email')}}" maxlength="255" autocomplete="email" required>
<label for="role">Rol</label><select id="role" name="role" required>@foreach(['usuario'=>'Usuario','jefe'=>'Jefe','admin'=>'Admin'] as $value=>$label)<option value="{{$value}}" @selected(old('role','usuario')===$value)>{{$label}}</option>@endforeach</select>
<label for="area_id">Área</label><select id="area_id" name="area_id" required><option value="">Selecciona un área</option>@foreach($areas as $area)<option value="{{$area->id}}" @selected((string)old('area_id')===(string)$area->id)>{{$area->nombre}}</option>@endforeach</select>
</div><div>
<label for="password">Contraseña (mínimo 12 caracteres)</label><input id="password" type="password" name="password" minlength="12" maxlength="255" autocomplete="new-password" required>
<label for="password_confirmation">Confirmar contraseña</label><input id="password_confirmation" type="password" name="password_confirmation" minlength="12" maxlength="255" autocomplete="new-password" required>
<p>Jefe y admin pueden crear cuentas y enviar correos. El rol jefe conserva la administración del flujo de facturas.</p>
</div></div>
<button>Registrar usuario</button>
</form></div>
<div class="card"><h2>Usuarios registrados</h2><div class="tablewrap"><table>
<thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Área</th><th>Acciones</th></tr></thead>
<tbody>@forelse($users as $user)<tr><td>{{$user->name}}</td><td>{{$user->email}}</td><td>{{$user->role}}</td><td>{{$user->area?->nombre ?? 'Sin área'}}</td><td><a href="{{route('users.mail',$user)}}">Enviar correo</a></td></tr>@empty<tr><td colspan="5">No hay usuarios registrados.</td></tr>@endforelse</tbody>
</table></div>@include('facturas.pagination',['page'=>$users])</div>
@endsection
