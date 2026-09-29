SISTEMA DE FACTURAS v0.4
Versión de desarrollo — Laravel, PHP y SQLite

Aplicación para registrar facturas PDF, asignarlas a áreas y dar seguimiento al pago. Esta versión conserva las funciones de v0.3 y agrega gestión de usuarios y notificaciones por correo, incluido el envío del PDF al asignar una factura para pago.

1. FUNCIONES

- Inicio y cierre de sesión.
- Registro de facturas PDF, proveedor y folio.
- Asignación de facturas a áreas.
- Estados: recibida, por_pagar, en_revision, correccion y confirmada.
- Carga y revisión de comprobantes de pago.
- Solicitudes de corrección y confirmación del pago.
- Historial, notificaciones internas y versiones de documentos.
- Edición, eliminación recuperable y restauración desde la papelera.
- Registro y listado de usuarios, exclusivos para jefe/admin.
- Envío manual de correo a usuarios registrados, exclusivo para jefe/admin.
- Correo automático individual con PDF e instrucciones a los usuarios del área al asignar una factura para pago (acción del jefe).
- Validación de correo único, confirmación de contraseña y almacenamiento con hash.
- Autorización en el servidor: conocer una URL no permite saltarse los permisos.

2. REQUISITOS

Los comandos siguientes son para Windows PowerShell. Ejecútalos desde la carpeta que contiene el archivo artisan.

- PHP 8.3 o superior compatible con composer.lock.
- Composer.
- SQLite mediante la extensión pdo_sqlite de PHP. No se necesita instalar un servidor MySQL.
- Extensiones PHP requeridas por la aplicación y dependencias: mbstring, pdo_sqlite, fileinfo, openssl, dom y xml, entre otras que Composer comprueba.
- Git, solamente si vas a clonar o actualizar desde GitHub.

Las vistas actuales incluyen su CSS. No necesitas Node.js, npm ni ejecutar Vite para utilizar estas pantallas. El correo manual es síncrono: tampoco necesita un proceso queue:work.

Comprueba las herramientas:

    php -v
    composer --version
    php --ini
    php -m

Si PowerShell no reconoce php o composer, instala la herramienta o añade su carpeta al PATH; después abre otra terminal. Puedes localizar el ejecutable activo con:

    Get-Command php
    Get-Command composer

3. PREPARAR PHP

Ejecuta:

    php --ini

Abre el archivo indicado por Loaded Configuration File. No edites un php.ini de otra instalación.

En una instalación manual de PHP para Windows, comprueba extension_dir y habilita las extensiones necesarias quitando el punto y coma inicial de las líneas que existan:

    extension_dir = "ext"
    extension=mbstring
    extension=pdo_sqlite
    extension=fileinfo
    extension=openssl

Si la ruta relativa ext no se resuelve correctamente, usa la ruta absoluta de la carpeta ext de tu instalación de PHP. Los DLL deben corresponder a esa misma instalación. Algunas extensiones ya están integradas: comprueba php -m antes de intentar añadirlas.

Para subir los PDFs de hasta 10 MB que admite la aplicación, configura también:

    upload_max_filesize = 10M
    post_max_size = 12M

Guarda y reinicia el servidor PHP si estaba ejecutándose. Comprueba:

    php -m | Select-String 'mbstring|pdo_sqlite|fileinfo|openssl|dom|xml'
    php -r "var_dump(function_exists('mb_strimwidth'));"

La última comprobación debe devolver bool(true). En PowerShell usa Select-String; no necesitas grep.

4. INSTALACIÓN NUEVA

Esta sección es para una instalación sin datos. Si tienes información en v0.3, usa la sección 11.

Paso 1. Descarga y extrae el ZIP v0.4 en una carpeta nueva, o clona el repositorio que contenga esta versión. No sobrescribas la carpeta v0.3.

Paso 2. Abre PowerShell en la carpeta del proyecto. Por ejemplo, si lo extrajiste allí:

    cd "$env:USERPROFILE\Desktop\sistema_facturas_v0.4"
    Test-Path .\artisan

La comprobación debe devolver True. Si no, entra en la subcarpeta correcta.

Paso 3. Instala las versiones de dependencias incluidas en composer.lock:

    composer install
    composer check-platform-reqs

No uses composer update como parte de la instalación: actualizaría las versiones fijadas. Si un comando falla, resuelve el error antes de continuar.

Paso 4. Crea la configuración privada solamente si no existe:

    if (!(Test-Path .env)) { Copy-Item .env.example .env }
    notepad .env

Para desarrollo local comprueba estos valores:

    APP_NAME="Sistema de Facturas"
    APP_ENV=local
    APP_DEBUG=true
    APP_URL=http://127.0.0.1:8000
    DB_CONNECTION=sqlite
    MAIL_MAILER=log

Deja DB_DATABASE sin definir para usar database/database.sqlite. Si copiaste una configuración anterior, revisa que no apunte a la base original ni tenga DB_URL con otra conexión.

Paso 5. Genera la clave, solo en esta instalación nueva:

    php artisan key:generate

No regeneres la clave de una instalación existente.

Paso 6. Crea la base si falta y prepara las tablas y cuentas demo:

    if (!(Test-Path database/database.sqlite)) { New-Item -ItemType File -Path database/database.sqlite }
    php artisan config:clear
    php artisan migrate
    php artisan db:seed

El seeder crea las áreas y cuentas de demostración únicamente con APP_ENV=local o testing. Si una cuenta ya existe, conserva su contraseña y permisos.

Paso 7. Ejecuta el servidor:

    php artisan serve --host=127.0.0.1 --port=8000

Abre en el navegador:

    http://127.0.0.1:8000

Mantén PowerShell abierto. Para detener el servidor, presiona Ctrl+C.

5. EJECUTAR EL PROYECTO LOS DÍAS SIGUIENTES

Abre PowerShell, entra en la carpeta del proyecto y ejecuta:

    php artisan serve --host=127.0.0.1 --port=8000

No repitas key:generate ni vuelvas a crear la base. No necesitas reinstalar dependencias cada vez.

También se incluye iniciar.ps1. Después de completar la instalación puedes ejecutarlo con:

    .\iniciar.ps1

Ese script ejecuta las migraciones pendientes y levanta el servidor con límites de carga de 10 MB/12 MB. Si PowerShell bloquea el script por su política de ejecución, usa php artisan serve y configura los límites en php.ini como se indica arriba.

6. CUENTAS DEMO

Las siguientes cuentas están definidas en DemoUsersSeeder. Las contraseñas corresponden a cuentas nuevas creadas por ese seeder:

Jefe de Informática
    Correo: jefe@example.test
    Contraseña: JefeDemo!2026
    Rol: jefe
    Área: Informática

Usuario de Informática
    Correo: informatica@example.test
    Contraseña: InformaticaDemo!2026
    Rol: usuario
    Área: Informática

Usuario de Finanzas
    Correo: finanzas@example.test
    Contraseña: FinanzasDemo!2026
    Rol: usuario
    Área: Finanzas

Usuario de Vivienda
    Correo: vivienda@example.test
    Contraseña: ViviendaDemo!2026
    Rol: usuario
    Área: Vivienda

No hay cuenta admin demo. Puedes crearla entrando como jefe. Las direcciones .test no sirven para recibir correo real. Estas cuentas son únicamente para desarrollo.

7. ROLES Y REGISTRO DE USUARIOS

Jefe:
    Conserva la administración del flujo de facturas. Además, registra usuarios, consulta el listado y envía correos.

Admin:
    Registra usuarios, consulta el listado y envía correos. En v0.4 no hereda automáticamente los permisos del jefe para administrar facturas.

Usuario:
    Accede al flujo permitido para su área. No puede administrar usuarios ni enviar correos desde estas funciones.

Para registrar una cuenta:

1) Inicia sesión como jefe o admin.
2) Abre Usuarios.
3) Completa nombre, correo, contraseña, confirmación, rol y área.
4) Usa una contraseña de al menos 12 caracteres.
5) Presiona Registrar usuario.
6) Comprueba el mensaje de éxito y busca la cuenta en el listado paginado.
7) Cierra sesión y comprueba que la cuenta nueva puede ingresar.

El correo debe ser único. La contraseña se guarda con hash y no se muestra ni se envía por correo. Las áreas se toman de la tabla existente; esta versión no incluye un editor de áreas ni recuperación de contraseña desde la nueva gestión.

8. PRUEBA DE CORREO SIN ENVÍO REAL

En .env configura:

    MAIL_MAILER=log

Aplica la configuración:

    php artisan config:clear

Entra como jefe/admin, abre Usuarios y selecciona Enviar correo junto a una cuenta. Completa asunto y mensaje y envía.

La pantalla informa que es una prueba. Con la configuración de registro incluida, revisa el resultado con:

    Get-Content storage/logs/laravel.log -Tail 100

Este modo no entrega mensajes en ninguna bandeja. No confundas la prueba log con una entrega SMTP.

9. CONFIGURAR CORREO REAL POR SMTP

Obtén del proveedor el servidor, puerto, usuario, credencial SMTP y remitente autorizado. Edita únicamente tu .env privado; no pongas credenciales reales en .env.example ni en GitHub.

Ejemplo que debes reemplazar con los datos de tu proveedor:

    MAIL_MAILER=smtp
    MAIL_SCHEME=smtp
    MAIL_HOST=smtp.example.com
    MAIL_PORT=587
    MAIL_USERNAME=usuario_de_ejemplo
    MAIL_PASSWORD="clave_de_ejemplo"
    MAIL_FROM_ADDRESS=facturas@example.com
    MAIL_FROM_NAME="Sistema de Facturas"

Para TLS implícito, si el proveedor lo requiere, usa MAIL_SCHEME=smtps y MAIL_PORT=465. La configuración incluida utiliza MAIL_SCHEME; no lee MAIL_ENCRYPTION. No definas MAIL_URL si configuras host y credenciales por separado.

Después de editar:

    php artisan config:clear

Registra una cuenta con una dirección real propia y envíale un mensaje desde Usuarios. Comprueba la bandeja y spam. El destinatario procede del usuario registrado; el remitente procede de MAIL_FROM_*.

El envío se realiza al pulsar el botón, sin worker. Hay un límite de 10 solicitudes por minuto por usuario. La aceptación del mensaje por el servicio no garantiza su recepción final.

Si aparece un error, revisa el registro privado y los datos SMTP. Si el resultado es ambiguo, consulta el proveedor antes de reintentar para evitar duplicados. No compartas registros que contengan datos privados.

Las notificaciones internas del flujo de facturas siguen funcionando. El correo manual no crea una notificación interna porque los avisos actuales requieren una factura asociada.

9.1. ENVIAR LA FACTURA PDF AL ÁREA PARA PAGAR

Este envío se inicia desde la factura, no desde el formulario de correo manual de Usuarios. La asignación continúa siendo una función exclusiva del rol jefe; admin conserva la gestión de usuarios y el correo manual, sin ampliar sus permisos sobre facturas.

1) Configura MAIL_* como se explica arriba. Prueba primero con MAIL_MAILER=log y después con SMTP.
2) Crea usuarios con rol usuario, correo real y el área correspondiente. Una cuenta jefe/admin del área no forma parte de esta lista de destinatarios.
3) Inicia sesión como jefe y registra una factura PDF.
4) Abre su detalle. En Enviar PDF y notificar, selecciona el área responsable.
5) Revisa los destinatarios que muestra la pantalla y escribe las instrucciones para el pago en Mensaje para el área.
6) Pulsa Enviar PDF y notificación.
7) La factura pasa a Por pagar. Se guardan el historial y los avisos internos; después se intenta enviar un correo individual a cada usuario del área.
8) Cada correo incluye el PDF vigente de la factura, identificado como factura-ID.pdf, proveedor, folio, área, instrucciones y enlace para subir el comprobante. No adjunta comprobantes de pago ni versiones anteriores del PDF.
9) El destinatario coordina con su área para evitar pagos duplicados, realiza el pago externamente e ingresa al sistema para adjuntar el comprobante. El pago queda sujeto a revisión del jefe.

El adjunto es el PDF completo; no es solamente un enlace. Los destinatarios se calculan en el servidor. Cada mensaje tiene un solo destinatario y no expone las direcciones de los demás mediante CC.

Configura APP_URL con la dirección desde la que los destinatarios realmente puedan entrar al sistema. http://127.0.0.1:8000 solo funciona en el mismo equipo que ejecuta el servidor. Recibir el PDF por correo no hace accesible una aplicación local desde otro equipo. En una instalación compartida, usa su dirección accesible y ejecuta php artisan config:clear después de cambiar APP_URL.

Resultado del envío:

- Con log/array, la pantalla informa cuántos correos se simularon; no se entregan en bandejas reales. El registro log contiene el correo MIME, incluido el adjunto codificado: no publiques ese registro.
- Con SMTP, se indica cuántos correos procesó el servicio. Comprueba recepción y spam. El proveedor puede limitar el tamaño total del mensaje; un adjunto de 10 MB ocupa más espacio al codificarse para correo.
- Si falla un destinatario, se intenta continuar con los demás y se muestran las direcciones cuyo envío no pudo confirmarse. La factura, su estado y las notificaciones internas se conservan.
- Si falta el PDF o no se puede leer, no se envían correos y se muestra un error. La asignación y los avisos internos permanecen guardados.
- Una asignación rechazada por permisos, área sin usuarios, versión obsoleta o fallo de la transacción no envía correos.
- No hay reintentos automáticos. Volver a asignar mientras la factura está Por pagar vuelve a enviar a TODOS los usuarios del área seleccionada, incluidos quienes ya recibieron el mensaje. Revisa primero el resultado para evitar duplicados.
- Al reasignar a otra área, los nuevos correos van solo a los usuarios de esa área. El PDF ya enviado por correo al área anterior no puede retirarse; el acceso dentro del sistema sí se rige por la asignación vigente.

No se necesita queue:work: el envío es síncrono y puede tardar según la cantidad de usuarios y la respuesta del proveedor. Espera el resultado antes de repetir la acción.

10. COMPROBACIONES Y PRUEBAS

Desde la carpeta del proyecto:

    php artisan config:clear
    php artisan route:list
    php artisan test
    php artisan view:cache
    php artisan view:clear
    composer validate --no-check-publish

En la entrega v0.4 se aprobaron 33 pruebas y 379 verificaciones. También se comprobaron sintaxis PHP, rutas, compilación de vistas y configuración. Las pruebas usan SQLite en memoria y correo simulado/log; no verifican la entrega de tu proveedor SMTP.

Prueba manual recomendada:

1) Con jefe, crea un usuario de Finanzas.
2) Comprueba que correo repetido y confirmación incorrecta muestran errores.
3) Con usuario normal, abre /usuarios: debe responder 403.
4) Con jefe, registra un PDF y asígnalo a Finanzas.
5) Con Finanzas, sube un comprobante.
6) Con jefe, solicita una corrección y luego confirma el pago corregido.
7) Con otro usuario de otra área, comprueba que la factura no sea accesible.
8) Asigna una factura con instrucciones a un área con dos cuentas propias: verifica dos correos individuales, el PDF adjunto y el enlace.
9) Abre el PDF recibido y comprueba que corresponde a la factura actual. Prueba primero en log y después en SMTP con direcciones reales propias.

11. CONSERVAR LOS DATOS DE v0.3

No sigas la instalación nueva sobre tu base anterior.

1) Detén el servidor v0.3 para evitar cambios durante la copia.
2) Respalda toda la instalación, especialmente .env, database/database.sqlite y storage/app, que contiene los documentos.
3) Extrae v0.4 en otra carpeta.
4) Ejecuta composer install en v0.4.
5) Copia de forma privada el .env anterior, la base y los archivos de storage/app a la nueva carpeta. Conserva APP_KEY.
6) Revisa DB_DATABASE, DB_URL y cualquier ruta absoluta. Deben apuntar a la copia v0.4, nunca a la base original.
7) Ejecuta php artisan config:clear y php artisan migrate en v0.4.
8) No ejecutes key:generate ni migrate:fresh. No hace falta ejecutar db:seed si ya tienes usuarios y áreas.
9) Arranca v0.4 y verifica acceso, documentos e historial antes de usarla normalmente.

v0.4 no agrega migraciones respecto de v0.3. Copiar solo la base no conserva los PDFs: también necesitas storage/app. Mantén intacto el respaldo original.

12. SOLUCIÓN DE PROBLEMAS

Could not open input file: artisan
    Estás en otra carpeta. Entra donde se encuentra artisan y comprueba Test-Path .\artisan.

Falta vendor/autoload.php
    Ejecuta composer install en la carpeta del proyecto.

Composer informa requisitos de plataforma incumplidos
    Comprueba php -v, php --ini y composer check-platform-reqs. Habilita la extensión indicada o utiliza una versión compatible de PHP. No ocultes el problema con --ignore-platform-reqs.

Call to undefined function mb_strimwidth
    Habilita mbstring en el php.ini que muestra php --ini. Reinicia el servidor y verifica la función como se explica en la sección 3.

Could not find driver
    Comprueba pdo_sqlite con php -m y habilítalo en el PHP utilizado por el servidor.

Database file does not exist
    Comprueba que database/database.sqlite exista y que DB_DATABASE no apunte a otra carpeta. Crea el archivo solo si es una instalación nueva; después ejecuta migrate.

No such table: users, sessions o cache
    Comprueba que la conexión apunte a la base correcta y ejecuta php artisan migrate.

No application encryption key has been specified
    En una instalación nueva ejecuta php artisan key:generate. En una instalación existente recupera el APP_KEY original desde su respaldo.

No puedes entrar con una cuenta demo
    En una instalación local nueva ejecuta php artisan db:seed. Si la cuenta ya existía, el seeder no restablece su contraseña.

Error 419 / página expirada
    Recarga el formulario e inicia sesión nuevamente. Usa siempre el mismo host, evita mezclar localhost con 127.0.0.1 y permite cookies. Comprueba las migraciones y la configuración de sesión.

Error 403 en Usuarios
    Comprueba que la cuenta tenga rol jefe o admin. El rechazo para usuarios normales es intencional.

Error 429 al enviar correo
    Has alcanzado el límite de solicitudes. Espera un minuto antes de reintentar.

El correo no llega
    Comprueba que MAIL_MAILER no siga en log/array. Ejecuta config:clear, verifica los datos SMTP y el remitente autorizado, usa una dirección real y revisa spam y registros del proveedor.

Falla la subida de un PDF
    Comprueba que sea PDF, que no supere 10 MB y que PHP tenga upload_max_filesize=10M y post_max_size=12M. Reinicia el servidor después de editar php.ini.

El puerto 8000 está ocupado
    Usa php artisan serve --host=127.0.0.1 --port=8001 y abre http://127.0.0.1:8001. Ajusta APP_URL si vas a mantener ese puerto.

Error de escritura en storage o bootstrap/cache
    Comprueba que esas carpetas existan y que la cuenta que ejecuta PHP pueda escribir en ellas.

Para revisar un error local:

    Get-Content storage/logs/laravel.log -Tail 100

13. ARCHIVOS PRINCIPALES DE v0.4

app/Http/Controllers/UserController.php
    Registro, listado y envío de correo con validaciones.
app/Providers/AppServiceProvider.php y routes/web.php
    Regla de autorización y rutas protegidas.
app/Mail/UserMessage.php
    Mensaje de correo de Laravel.
resources/views/users/ y resources/views/mail/user-message.blade.php
    Formularios, listado y plantilla de correo.
resources/views/layouts/app.blade.php
    Acceso a Usuarios para roles autorizados.
tests/Feature/UserManagementTest.php
    Pruebas de permisos, registro, validación, hash y correo.
.env.example
    Variables MAIL de ejemplo, sin credenciales reales.

Archivos de la actualización de correo con PDF:
app/Http/Controllers/WorkflowController.php
    Envío después de guardar la asignación, con manejo de fallos por destinatario.
app/Mail/InvoiceAssigned.php y resources/views/mail/invoice-assigned.blade.php
    Correo de asignación con instrucciones y PDF adjunto.
resources/views/facturas/show.blade.php
    Explicación del correo automático en el formulario de asignación.
tests/Feature/InvoiceMailTest.php
    Comprueba bytes y tipo del PDF, destinatarios, reasignación, permisos, fallos, archivo ausente y ausencia de envío si se revierte la asignación.

14. ARCHIVOS QUE NO DEBES PUBLICAR

No incluyas .env, vendor, node_modules, database.sqlite, otras bases privadas, documentos subidos, registros ni cachés generadas. Conserva composer.lock y .env.example sin secretos.

Las instrucciones anteriores son para desarrollo local. El servidor integrado no es un despliegue de producción. Para publicar una instalación, configura un servidor con raíz en public, HTTPS, APP_DEBUG=false y cuentas propias sin credenciales demo.

