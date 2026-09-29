<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · Facturas</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f3f5f8;color:#172b45;font:16px/1.5 system-ui,sans-serif}main{min-height:100vh;display:grid;grid-template-columns:1fr 1fr}.intro{background:#142b45;color:white;padding:clamp(32px,7vw,100px);display:flex;flex-direction:column;justify-content:center}.brand{font-size:19px;font-weight:700;letter-spacing:.3px;margin-bottom:70px}.tag{font-size:13px;color:#a4dfce;text-transform:uppercase;letter-spacing:2px}h1{font-size:clamp(32px,4vw,52px);line-height:1.12;letter-spacing:-1.5px;margin:18px 0 24px}.intro p{color:#c0d1e2;max-width:420px}.areas{display:flex;gap:8px;flex-wrap:wrap;margin-top:24px}.areas span{border:1px solid #476078;border-radius:30px;padding:7px 12px;font-size:13px}.login{padding:40px 24px;display:flex;align-items:center;justify-content:center}.card{width:100%;max-width:440px;background:#fff;border:1px solid #dce3eb;border-radius:16px;padding:36px}h2{font-size:28px;letter-spacing:-.7px;margin:0 0 8px}.sub{color:#53647a;margin:0 0 26px}label{display:block;font-weight:600;font-size:14px;margin:20px 0 8px}input{width:100%;border:1px solid #9babbd;border-radius:8px;padding:13px;font:inherit;background:#fff;color:#172b45}input[aria-invalid=true]{border-color:#a12b2b}input:focus-visible,button:focus-visible{outline:3px solid #d9a22e;outline-offset:3px}.password{display:flex;border:1px solid #9babbd;border-radius:8px}.password input{border:0;min-width:0}.toggle{border:0;background:none;color:#146c60;font-weight:600;padding:0 14px;cursor:pointer}.submit{width:100%;background:#146c60;color:#fff;border:0;border-radius:8px;padding:14px;font:inherit;font-weight:600;margin-top:26px;cursor:pointer}.submit:disabled{opacity:.65}.error{color:#962828;font-size:14px;margin:7px 0 0}.notice{background:#e1f3e9;color:#155b39;border-radius:8px;padding:12px;margin-bottom:18px}.help{font-size:13px;color:#53647a;margin:22px 0 0}.foot{border-top:1px solid #e4e9f0;padding-top:18px;margin-top:22px;font-size:12px;color:#53647a}@media(max-width:760px){main{grid-template-columns:1fr}.intro{padding:28px}.brand{margin-bottom:24px}h1{font-size:32px}.intro p,.areas{display:none}.login{padding:24px 16px}.card{padding:26px}.tag{font-size:11px}}
    </style>
</head>
<body>
<main>
    <section class="intro" aria-label="Sistema de facturas">
        <div class="brand">Facturas / Archivo</div>
        <span class="tag">Gestión de documentos</span>
        <h1>Un espacio para<br>cada área.</h1>
        <p>Organiza tus facturas y consulta los documentos que corresponden a tu equipo.</p>
        <div class="areas"><span>Finanzas</span><span>Informática</span><span>Vivienda</span></div>
    </section>
    <section class="login">
        <div class="card">
            <h2>Iniciar sesión</h2>
            <p class="sub">Ingresa con tu cuenta asignada.</p>
            @if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>@endif
            <form method="post" action="{{ route('login.store') }}" id="login-form">
                @csrf
                <label for="email">Correo electrónico</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required maxlength="255" autofocus placeholder="nombre@organizacion.cl" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" @error('email') aria-describedby="email-error" @enderror>
                @error('email')<p class="error" id="email-error" role="alert">{{ $message }}</p>@enderror
                <label for="password">Contraseña</label>
                <div class="password">
                    <input id="password" name="password" type="password" autocomplete="current-password" required maxlength="1024" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" @error('password') aria-describedby="password-error" @enderror>
                    <button type="button" class="toggle" id="toggle" aria-controls="password" aria-pressed="false" aria-label="Mostrar contraseña">Mostrar</button>
                </div>
                @error('password')<p class="error" id="password-error" role="alert">{{ $message }}</p>@enderror
                <button class="submit" id="login-submit" type="submit">Ingresar</button>
            </form>
            <p class="help">Tu cuenta determina el área y los documentos a los que puedes acceder. Si necesitas acceso, contacta al jefe de informática.</p>
            <div class="foot">Versión de demostración local</div>
        </div>
    </section>
</main>
<script>
const toggle=document.getElementById('toggle'),password=document.getElementById('password');
toggle.addEventListener('click',()=>{const show=password.type==='password';password.type=show?'text':'password';toggle.textContent=show?'Ocultar':'Mostrar';toggle.setAttribute('aria-pressed',String(show));toggle.setAttribute('aria-label',show?'Ocultar contraseña':'Mostrar contraseña');});
document.getElementById('login-form').addEventListener('submit',()=>{const button=document.getElementById('login-submit');button.disabled=true;button.textContent='Ingresando…';});
window.addEventListener('pageshow',()=>{const button=document.getElementById('login-submit');button.disabled=false;button.textContent='Ingresar';});
</script>
</body>
</html>

