# Sistema de Facturas — Parte 1 (v0.1)

Primera etapa del sistema desarrollado con Laravel y PHP.

## Objetivo de esta versión

Registrar documentos de facturas en formato PDF y permitir su descarga desde la aplicación.

## Funcionalidades

- Carga de archivos PDF.
- Validación de tipo de archivo y límite de 10 MB.
- Almacenamiento del PDF mediante Laravel Storage.
- Registro en base de datos de nombre, ruta y tamaño del documento.
- Listado de facturas registradas.
- Descarga del PDF almacenado.

## Base de datos

Esta versión utiliza la tabla `facturas` creada por:

`2026_09_28_000001_create_facturas_table.php`

Campos principales:

- `id`
- `nombre_original`
- `ruta_pdf`
- `tamano_bytes`
- `created_at`
- `updated_at`

## Tecnologías

- PHP 8.3+
- Laravel 13
- SQLite/SQL mediante Eloquent ORM
- Blade

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Crear la base SQLite:

```bash
touch database/database.sqlite
php artisan migrate
```

En Windows Git Bash puedes usar:

```bash
mkdir -p database
touch database/database.sqlite
php artisan migrate
```

Iniciar:

```bash
php artisan serve
```

## Evolución

Esta es la versión inicial. La siguiente etapa incorpora autenticación, roles y áreas.
