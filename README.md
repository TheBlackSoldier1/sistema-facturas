# Sistema de Facturas v0.4 — desarrollo

Aplicación Laravel 13 / PHP 8.3+ con SQLite. v0.4 incorpora gestión de usuarios, correo manual y envío del PDF al asignar facturas para pago, conservando el flujo de v0.3: carga de PDFs, áreas, estados, comprobantes, correcciones, confirmación, historial, notificaciones internas, papelera y restauración. No se crea un tag Git.

## Instalación nueva (PowerShell)

Se requiere PHP 8.3 o superior, Composer y extensiones mbstring, pdo_sqlite, fileinfo, openssl, dom y xml. Comprueba `php -m` y `php --ini` si hay errores de extensiones.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File -Path database/database.sqlite
php artisan migrate
php artisan db:seed
php artisan serve
```

Abre http://127.0.0.1:8000. Las vistas actuales usan CSS incorporado: no requieren npm para funcionar. `db:seed` crea las cuentas demo únicamente con APP_ENV=local/testing. No ejecutes estos comandos de instalación sobre la carpeta original v0.3 ni sobre una base existente. En Linux usa `cp` y `touch` en lugar de Copy-Item y New-Item.

## Datos existentes de v0.3

Trabaja en otra carpeta y respalda antes la base y storage/app. El ZIP no contiene datos, documentos subidos ni credenciales. Para conservar datos, copia privadamente la base SQLite y storage/app desde tu instalación, configura tu propio .env y conserva el APP_KEY original si necesitas leer datos cifrados existentes. Revisa DB_DATABASE y cualquier ruta absoluta para que apunten a la copia. Ejecuta `php artisan migrate` (nunca migrate:fresh). v0.4 no añade migraciones. No compartas esa copia privada ni su .env.

## Roles y usuarios

- `jefe`: mantiene todos sus permisos sobre facturas; además registra usuarios y envía correos.
- `admin`: registra usuarios, consulta el listado y envía correos. No hereda automáticamente los permisos del jefe sobre facturas; se preserva el comportamiento anterior del flujo.
- `usuario`: conserva el acceso a las facturas de su área; no puede acceder a la gestión ni enviar correos, incluso usando las URL directamente (403). Invitados van al login.

Desde **Usuarios**, registra nombre, correo único, contraseña de mínimo 12 caracteres y su confirmación, rol y área existente. Se guarda un hash; la contraseña no se muestra ni se envía por correo. Los correos nuevos se normalizan a minúsculas y se comprueba unicidad sin distinguir mayúsculas. El listado está paginado a 20 usuarios. Las áreas demo son Informática, Finanzas y Vivienda; se reutiliza la tabla existente y no se agrega un editor de áreas.

## Cuentas demo del seeder incluido

Estas son las cuentas que realmente define `database/seeders/DemoUsersSeeder.php`. Si una ya existe, el seeder no cambia su contraseña ni sus permisos; por eso las claves siguientes solo corresponden a cuentas nuevas creadas por ese seeder.

| Correo | Contraseña demo | Rol | Área |
|---|---|---|---|
| jefe@example.test | JefeDemo!2026 | jefe | Informática |
| informatica@example.test | InformaticaDemo!2026 | usuario | Informática |
| finanzas@example.test | FinanzasDemo!2026 | usuario | Finanzas |
| vivienda@example.test | ViviendaDemo!2026 | usuario | Vivienda |

No hay una cuenta admin predefinida: el jefe puede crearla desde Usuarios. Las direcciones .test no reciben correo real. Usa estas cuentas solo en desarrollo.

## Configuración MAIL y envío

Por defecto `MAIL_MAILER=log`: se renderiza el correo y se guarda en `storage/logs/laravel.log`, sin entregar mensajes reales. Para SMTP configura privadamente tu .env con los datos del proveedor:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=usuario_de_ejemplo
MAIL_PASSWORD=clave_de_ejemplo
MAIL_FROM_ADDRESS=facturas@example.com
MAIL_FROM_NAME="Sistema de Facturas"
```

Los valores son ejemplos, no credenciales utilizables. Para TLS implícito, si el proveedor lo indica, usa MAIL_SCHEME=smtps y puerto 465. El puerto 587 usa SMTP con STARTTLS cuando el servidor lo ofrece. Este proyecto usa MAIL_SCHEME, no MAIL_ENCRYPTION. Mantén MAIL_URL sin definir si configuras las variables separadas. Después ejecuta `php artisan config:clear`.

Inicia sesión como jefe/admin → Usuarios → Enviar correo junto al destinatario → escribe asunto y mensaje → Enviar correo. El destinatario se obtiene del usuario registrado en servidor. El remitente procede de MAIL_FROM_*, no del formulario. El envío es síncrono, no requiere un worker de colas, y está limitado a 10 solicitudes por minuto por usuario. El contenido se escapa como texto para impedir HTML inyectado.

La aplicación informa del modo log/array, éxito de procesamiento o fallo del servicio sin mostrar detalles técnicos al usuario. Que SMTP acepte el mensaje no garantiza recepción: revisa la bandeja y spam. Tras un fallo ambiguo verifica el proveedor antes de reintentar para evitar duplicados. Los errores técnicos quedan en los registros privados de Laravel.

Las notificaciones internas existentes siguen funcionando para el flujo de facturas. El correo manual no crea un aviso interno: la tabla avisos exige una factura asociada.

## Cómo probar

1. Con jefe, crea una cuenta usuario con un correo real propio y un área. Comprueba que aparece en el listado y puede iniciar sesión.
2. Prueba correo repetido, confirmación distinta y contraseña corta: deben mostrarse errores sin crear cuentas.
3. Con la cuenta usuario abre /usuarios y /usuarios/1/correo: debe responder 403. Las rutas POST están protegidas de igual manera.
4. Con jefe/admin envía primero en modo log y revisa el registro. Luego configura SMTP y envía a una dirección tuya; comprueba recepción. No uses las direcciones demo .test para esa prueba.
5. Comprueba el flujo original de facturas por área, comprobantes e historial.

```powershell
php artisan route:list
php artisan test
php artisan view:cache
php artisan view:clear
```

Las pruebas usan SQLite en memoria y transportes simulados/log, sin enviar correo a personas. UserManagementTest cubre ambos roles, bloqueo de usuarios/invitados, validación, hash, destinatario fijo, escape de HTML, fallos y modo log; se conservan las pruebas del flujo anterior.

## Archivos principales de v0.4

- app/Http/Controllers/UserController.php: listado, alta y envío con validaciones.
- app/Providers/AppServiceProvider.php y routes/web.php: autorización server-side y rutas protegidas.
- app/Mail/UserMessage.php y resources/views/mail/user-message.blade.php: correo Laravel.
- resources/views/users/* y resources/views/layouts/app.blade.php: formularios y navegación.
- tests/Feature/UserManagementTest.php: pruebas de la funcionalidad nueva.
- .env.example: solo se modifican valores MAIL de ejemplo; README.md: documentación v0.4.

El ZIP excluye .env, vendor, node_modules, bases SQLite, cachés, registros, documentos privados y .git. Instala las dependencias con composer install. Conserva composer.lock para reproducir versiones.

## Actualización v0.4: correo de asignación con PDF

El jefe abre una factura recibida o por pagar, selecciona el área, escribe las instrucciones y pulsa **Enviar PDF y notificación**. Después de guardar la asignación, se envía un correo individual a cada cuenta con rol `usuario` de esa área. Incluye el PDF vigente completo, proveedor, folio, instrucciones y enlace para subir el comprobante. Los avisos internos se mantienen. El rol admin no cambia sus permisos sobre el flujo de facturas.

Usa MAIL_MAILER=log para pruebas y SMTP para entrega real. Configura APP_URL con una dirección accesible para los destinatarios: 127.0.0.1 solo funciona en el equipo local. Ejecuta `php artisan config:clear` después de cambiar la configuración. No requiere worker de colas.

La pantalla informa cuántos correos procesó el servicio o simuló el modo de prueba. Si falla un destinatario, se continúa con los demás y se muestran los fallos; se conserva la asignación. Si no se puede leer el PDF, se informa el error y no se envían correos. Las asignaciones rechazadas o revertidas no envían mensajes.

No hay reintentos automáticos. Volver a asignar envía nuevamente a todos los usuarios del área elegida; revisa los resultados para evitar duplicados. Al reasignar, solo se envía al área nueva, pero no se pueden retirar adjuntos ya recibidos por correo. Coordina el pago dentro del área antes de realizarlo.

La actualización añade InvoiceAssigned, su plantilla y InvoiceMailTest, y modifica WorkflowController y el formulario de asignación. Suite verificada: 33 pruebas, 379 verificaciones; no se realizó entrega SMTP real. Prueba con dos cuentas propias del área, revisa el adjunto recibido y confirma que corresponde al PDF actual. El correo manual de Usuarios sigue siendo de asunto y texto, sin adjunto.

Consulta **README.txt** para el procedimiento completo en texto plano: instalación PowerShell, ejecución, cuentas demo, SMTP, envío de factura para pago, respaldo y solución de problemas.

## Historial de versiones

Este historial resume los hitos documentados y el código disponible; no atribuye fechas de publicación ni supone que existan tags Git. La documentación original de la carpeta v0.3 llamó al flujo completo «Parte 3 (v1.0)». Aquí se identifica ese mismo hito como **v0.3** para mantener la secuencia de desarrollo utilizada en este proyecto.

| Versión | Cambios principales | Resultado |
| --- | --- | --- |
| v0.1 | Registro y almacenamiento básico de archivos PDF de facturas. | Base del registro documental. |
| v0.2 | Autenticación, roles y áreas. | Acceso identificado y organización de facturas por área. |
| v0.3 | Flujo de estados; proveedor y folio; asignación de áreas; comprobantes; revisión, corrección y confirmación; historial y avisos internos; edición, papelera y restauración; conservación de documentos; filtros y control de versión del formulario. | Seguimiento completo del pago externo y su revisión. |
| v0.4 | Gestión y listado de usuarios para jefe/admin; correo único, contraseña confirmada y hash; autorización en servidor; correo manual; configuración MAIL y documentación de instalación. | Creación de cuentas y comunicación desde la aplicación. |
| v0.4, ampliación | Correo individual con el PDF vigente e instrucciones al asignar una factura; manejo de fallos parciales y conservación de avisos internos; pruebas y documentación adicionales. | Usuarios del área reciben la factura para gestionar su pago. No constituye v0.5 ni crea un tag. |

La v0.4 reutiliza las tablas de v0.3 y no añade migraciones. Conserva la asignación y administración de facturas exclusivamente para el jefe; el nuevo rol admin administra usuarios y correo manual.
