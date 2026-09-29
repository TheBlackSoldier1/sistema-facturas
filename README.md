# Sistema de Facturas — Parte 2 (v0.2)

Segunda etapa del sistema. Mantiene el registro de PDFs de la Parte 1 e incorpora control de acceso mediante usuarios, roles y áreas.

## Cambios respecto de v0.1

- Inicio y cierre de sesión.
- Rol `jefe` y rol `usuario`.
- Creación de áreas.
- Asociación de usuarios a un área.
- Asociación opcional de una factura a un área.
- Registro del usuario que cargó la factura.
- El jefe puede ver todas las facturas.
- El usuario normal solamente puede acceder a las facturas de su área.

## Migraciones de esta versión

1. `2026_09_28_000001_create_facturas_table.php`
2. `2026_09_28_000002_add_roles_and_areas.php`

La segunda migración agrega:

- tabla `areas`
- `role` y `area_id` en `users`
- `area_id` y `uploaded_by` en `facturas`

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

## Usuarios de demostración

Los usuarios se generan con `DemoUsersSeeder` solo en entorno local/testing. Revisa ese archivo para las cuentas de prueba.

## Evolución

La Parte 3 incorpora el flujo completo de la factura: estados, comprobantes, historial, notificaciones, correcciones y confirmación de pago.
