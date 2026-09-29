# Sistema de Facturas — Parte 3 (v1.0)

Versión actual y más completa del sistema de seguimiento de facturas desarrollado con Laravel, PHP y SQL/SQLite.

## Cambios respecto de v0.2

Se incorpora el flujo completo de seguimiento de una factura.

### Estados

- `recibida`
- `por_pagar`
- `en_revision`
- `correccion`
- `confirmada`

### Funcionalidades nuevas

- Registro de proveedor y folio.
- Asignación de facturas a áreas.
- Comprobantes de pago.
- Revisión del comprobante por parte del jefe.
- Solicitud de corrección.
- Confirmación de pago.
- Historial de acciones y cambios.
- Notificaciones internas.
- Control de versiones para evitar aplicar formularios obsoletos.
- Edición de datos de factura.
- Eliminación recuperable mediante papelera.
- Restauración de facturas.
- Conservación de documentos asociados.
- Búsqueda y filtros por estado.

## Migraciones

1. `2026_09_28_000001_create_facturas_table.php`
2. `2026_09_28_000002_add_roles_and_areas.php`
3. `2026_09_28_000003_add_invoice_workflow.php`

La tercera migración agrega los campos de workflow y las tablas:

- `documentos`
- `eventos`
- `avisos`

## Flujo general

```text
Factura recibida
      ↓
Recibida
      ↓
Asignada a un área
      ↓
Por pagar
      ↓
Usuario realiza el pago externamente
      ↓
Sube comprobante
      ↓
En revisión
      ↓
┌─────────────────┴──────────────────┐
Corrección requerida          Pago confirmado
      ↓                              ↓
Nuevo comprobante               Confirmada
```

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

## Nota de seguridad

No subas al repositorio:

- `.env`
- `vendor/`
- `node_modules/`
- `database/database.sqlite`

El `.gitignore` del proyecto ya excluye estos archivos.

## Historial del proyecto

- **v0.1:** registro y almacenamiento básico de PDFs.
- **v0.2:** autenticación, roles y áreas.
- **v1.0:** workflow completo de facturas, comprobantes, estados, historial y notificaciones.
