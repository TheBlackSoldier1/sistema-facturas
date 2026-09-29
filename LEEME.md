# Facturas: registro, pago externo y revisión

Aplicación Laravel 13 con PHP y SQLite. Se ejecuta localmente en http://127.0.0.1:8000. Los PDFs se conservan en almacenamiento privado; las tablas guardan sus referencias, estados, actores y fechas.

## Iniciar

En PowerShell, dentro de esta carpeta:

~~~powershell
.\iniciar.ps1
~~~

Mantén la terminal abierta. Ctrl+C detiene el servidor. Si el puerto está ocupado, la aplicación podría seguir ejecutándose en otra terminal. El iniciador escucha únicamente en 127.0.0.1.

## Cuentas locales de demostración

| Rol / área | Correo | Contraseña inicial |
| --- | --- | --- |
| Jefe de informática | jefe@example.test | JefeDemo!2026 |
| Finanzas | finanzas@example.test | FinanzasDemo!2026 |
| Informática | informatica@example.test | InformaticaDemo!2026 |
| Vivienda | vivienda@example.test | ViviendaDemo!2026 |

Las contraseñas se guardan con hash. El seeder solo funciona en local/testing y no restablece cuentas existentes. Estas credenciales son conocidas y solo sirven para la demostración. No publiques esta instalación con estas cuentas.

## Proceso implementado

1. El jefe recibe una factura fuera de la aplicación.
2. En **Facturas y estados → Registrar factura recibida**, sube su PDF (máximo 10 MB). Queda **Recibida**, sin área.
3. Abre **Administrar**. Puede editar proveedor y folio, y actualizar el PDF antes de asignarlo. Las versiones previas se conservan.
4. En **Enviar PDF y notificar**, selecciona Finanzas, Informática o Vivienda. Verás los usuarios destinatarios y podrás agregar instrucciones. Pulsa **Enviar PDF y notificación**: queda **Por pagar** y los usuarios del área reciben un aviso interno con accesos directos a **Descargar PDF** y **Subir comprobante**.
5. El usuario del área consulta la factura, realiza el pago externamente y sube el comprobante desde **Ver factura → Informar pago realizado**.
6. La factura pasa a **En revisión**. Los jefes reciben una notificación.
7. El jefe abre la vista previa del comprobante más reciente:
   - Si todo está correcto, pulsa **Confirmar pago**. Queda **Confirmada**, se notifica al área y aparece en su **Historial de pagos**.
   - Si encuentra un error, escribe una observación y pulsa **Solicitar corrección**. Queda **Requiere corrección** y el área recibe el motivo.
8. El área envía otro comprobante. La factura vuelve a **En revisión**, conservando el anterior.

La aplicación no ejecuta pagos, no comprueba transferencias bancarias automáticamente y no extrae datos de los PDFs. La confirmación es una decisión del jefe después de revisar la evidencia.

## Estados y permisos

| Estado | Acción siguiente | Quién |
| --- | --- | --- |
| Recibida | Editar datos/PDF y asignar | Jefe |
| Por pagar | Subir comprobante; el jefe puede reasignar antes de su envío | Área / jefe |
| En revisión | Confirmar o solicitar corrección con motivo | Jefe |
| Requiere corrección | Subir una nueva versión del comprobante | Área |
| Confirmada | Consultar, previsualizar y descargar | Área y jefe |

El jefe administra todas las facturas, edita sus datos y puede eliminarlas de forma recuperable o restaurarlas. La edición de proveedor/folio queda registrada y no modifica una confirmación previa. Los documentos enviados no se sustituyen silenciosamente.

El usuario solo tiene acceso a las facturas asignadas a su área. No puede registrar facturas originales, editar datos, aprobar pagos, eliminar ni consultar la auditoría global. Una factura confirmada es de solo lectura para el área. No puede enviar un nuevo comprobante después de confirmada.

## Pantallas

- **Facturas y estados:** listado con filtros por estado y búsqueda por nombre, proveedor o folio.
- **Detalle de factura:** documentos, vista previa, descarga y acciones permitidas según estado y rol.
- **Historial completo:** solo jefe; todas las acciones con fecha, actor, estado anterior/nuevo y detalle. Incluye eliminaciones y restauraciones.
- **Historial de pagos:** cada área consulta sus confirmaciones; los documentos permiten solo vista previa/descarga.
- **Papelera:** solo jefe; conserva PDFs e historial y permite restaurar.
- **Notificaciones:** bandeja individual, contador de pendientes y marcar como leída.

Los avisos se generan al asignar, recibir comprobantes, solicitar correcciones, confirmar, eliminar o restaurar. Se consultan al cargar las páginas; no hay correo ni notificaciones push en esta versión. Si una factura se reasigna, el área anterior recibe el aviso y pierde acceso al documento.

## Qué significa CRUD aquí

- Crear: registrar factura recibida.
- Leer: listado, estado, documentos y auditoría.
- Actualizar: proveedor/folio y PDF original antes del envío; cambios de estado mediante las acciones del proceso.
- Eliminar: mover a la papelera con motivo obligatorio. La eliminación no destruye la evidencia.

El historial es una bitácora: no tiene botones para editar o borrar eventos individuales. Así las acciones del CRUD también quedan registradas. Los eventos se generan en el servidor; el formulario no puede atribuir una acción a otro usuario.

## Arquitectura para aprender

- Las rutas de **routes/web.php** exigen sesión y dirigen cada petición.
- **AuthController** valida credenciales y administra sesiones.
- **FacturaController** registra originales y construye el listado.
- **WorkflowController** comprueba permisos, aplica transiciones y coordina avisos e historial.
- **Factura** conserva estado y área; **Documento** conserva cada PDF y su autor; **Evento** registra cada acción; **Aviso** guarda las notificaciones de cada destinatario.
- Las vistas Blade presentan únicamente las acciones aplicables. El servidor vuelve a comprobar todos los permisos: ocultar botones no basta.

Cada transición, evento y aviso se guarda en una misma transacción. Una versión numérica permite detectar formularios desactualizados. Si otra acción ya cambió la factura, se pide recargar. Si falla el registro de un comprobante, se retira el nuevo archivo y se revierte el cambio de estado.

Los documentos anteriores a esta actualización se incorporaron como **Recibida**, sin asignación, con un evento explícito de incorporación. No se inventó un historial anterior; el jefe debe enviarlos al área correspondiente.

## Pruebas

~~~powershell
php artisan test
~~~

La suite usa una base de datos en memoria y almacenamiento separado. Cubre login, límites y formato de archivos, descarga íntegra, separación entre áreas, el ciclo con corrección y confirmación, historial de solo lectura para áreas, CRUD recuperable, avisos, formularios desactualizados y reversión ante fallos.

Prueba manual recomendada:
1. Jefe: registrar un PDF de prueba y asignarlo a Finanzas.
2. Finanzas: abrir notificaciones y enviar comprobante.
3. Jefe: pedir una corrección con un motivo concreto.
4. Finanzas: leer el motivo y enviar otra versión.
5. Jefe: revisar y confirmar.
6. Finanzas: comprobar el aviso de confirmación y que su historial no tenga controles de edición.
7. Vivienda: comprobar que esa factura no aparece ni puede abrirse mediante su URL.

## Respaldo y puesta en servicio

Con el servidor detenido, respalda juntos **database/database.sqlite**, **storage/app/private/facturas**, el código y el archivo **.env** privado. Copiar solo la base de datos no respalda los PDFs. No uses migrate:fresh sobre datos que quieras conservar.

Antes del uso compartido: reemplazar cuentas de prueba, administrar usuarios y contraseñas, configurar HTTPS y APP_DEBUG=false, revisar respaldo/restauración y acordar límites, retención y revisión de archivos con la organización. La validación de PDF no equivale a un análisis antivirus.

## Instalación en otra carpeta o equipo

Con PHP 8.3+ y Composer, extensiones pdo_sqlite, fileinfo, mbstring, openssl, xml y dom:

~~~powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item database/database.sqlite -ItemType File
php artisan migrate
php artisan db:seed --class=DemoUsersSeeder
.\iniciar.ps1
~~~

Solo para una instalación nueva: no sobrescribas un .env existente ni regeneres las claves de una instalación en uso. Mantén DB_CONNECTION=sqlite. No se necesita Vite para estas vistas.

Referencias: [autenticación](https://laravel.com/framework/docs/13.x/authentication), [transacciones](https://laravel.com/framework/docs/database) y [Eloquent](https://laravel.com/framework/docs/eloquent).
