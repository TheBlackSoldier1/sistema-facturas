@extends('layouts.app')
@section('title', 'Enviar correo · Facturas')
@section('content')
<h1>Enviar notificación por correo</h1>
<p><a href="{{route('users.index')}}">Volver a usuarios</a></p>
<div class="card"><h2>Para: {{$user->name}}</h2><p>{{$user->email}}</p>
@if(in_array(config('mail.default'),['log','array']))<div class="notice">Modo de prueba: el correo no llegará a una bandeja real. Configura MAIL_MAILER=smtp para realizar envíos.</div>@endif
<form method="post" action="{{route('users.mail.send',$user)}}">@csrf
<label for="subject">Asunto</label><input id="subject" name="subject" value="{{old('subject')}}" maxlength="150" required>
<label for="message">Mensaje</label><textarea id="message" name="message" maxlength="5000" rows="8" required>{{old('message')}}</textarea>
<button>Enviar correo</button>
</form></div>
@endsection
