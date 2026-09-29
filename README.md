# Sistema de Gestión de Facturas

Sistema web desarrollado con Laravel, PHP y SQL/SQLite para registrar, almacenar y realizar seguimiento al proceso de gestión de facturas.

El proyecto fue desarrollado de forma progresiva y se encuentra dividido en tres etapas principales:

- v0.1 — Parte 1: registro y almacenamiento básico de facturas.
- v0.2 — Parte 2: autenticación, usuarios, roles y áreas.
- v0.3 — Parte 3: workflow de seguimiento, comprobantes, historial y notificaciones.

Estado actual: proyecto en desarrollo.

---

# Tecnologías utilizadas

- PHP
- Laravel
- Blade
- HTML
- CSS
- JavaScript
- SQL / SQLite
- Composer
- Git
- GitHub

---

# Parte 1 — Registro básico de facturas

## Versión v0.1

La primera etapa del proyecto establece la estructura inicial del sistema y permite registrar facturas junto con sus documentos PDF.

### Funcionalidades implementadas

- Creación inicial del proyecto Laravel.
- Registro de facturas.
- Almacenamiento de información de la factura.
- Registro de datos básicos.
- Carga de documentos PDF.
- Almacenamiento de la ruta del documento.
- Visualización de facturas registradas.
- Descarga de archivos PDF.
- Validaciones básicas de formulario.

### Componentes principales

En esta etapa se trabaja principalmente con:

- FacturaController
- Modelo Factura
- Vistas Blade de facturas
- Rutas web
- Base de datos
- Almacenamiento de documentos

### Base de datos

La primera migración corresponde a:

2026_09_28_000001_create_facturas_table.php

Esta migración crea la estructura inicial necesaria para almacenar las facturas.

### Flujo inicial

Usuario
   ↓
Formulario de factura
   ↓
FacturaController
   ↓
Validación
   ↓
Guardar información
   ↓
Guardar PDF
   ↓
Base de datos

---

# Parte 2 — Autenticación, roles y áreas

## Versión v0.2

La segunda etapa amplía el sistema incorporando autenticación y una estructura de usuarios con diferentes responsabilidades.

### Cambios respecto de v0.1

Se incorporan:

- Inicio de sesión.
- Autenticación de usuarios.
- Usuarios del sistema.
- Roles.
- Áreas o departamentos.
- Asociación entre usuarios y áreas.
- Identificación del usuario que registra una factura.
- Restricciones según el tipo de usuario.

### Roles

El sistema diferencia principalmente entre:

- Jefe
- Usuario

Esto permite preparar la plataforma para que determinadas acciones puedan ser realizadas dependiendo de las responsabilidades del usuario.

### Áreas

Las facturas pueden relacionarse con las diferentes áreas existentes dentro de la organización.

Esto permite organizar posteriormente el proceso de asignación y seguimiento.

### Componentes agregados

Entre los principales elementos incorporados se encuentran:

- AuthController.php
- Area.php
- User.php
- DemoUsersSeeder.php
- resources/views/auth/

También se modifican componentes existentes como:

- FacturaController.php
- Factura.php
- routes/web.php
- resources/views/facturas/

### Segunda migración

2026_09_28_000002_add_roles_and_areas.php

Esta etapa amplía la estructura inicial para soportar usuarios, roles y áreas.

### Flujo general de la Parte 2

Usuario
   ↓
Inicio de sesión
   ↓
Autenticación
   ↓
Rol del usuario
   ↓
Área asociada
   ↓
Acceso al sistema de facturas

---

# Parte 3 — Workflow de seguimiento de facturas

## Versión v0.3

La tercera etapa incorpora el flujo de seguimiento de una factura desde su recepción hasta la confirmación de su pago.

Esta corresponde a la versión actual del proyecto.

### Cambios respecto de v0.2

Se agregan mecanismos para controlar el avance de las facturas, almacenar comprobantes, registrar acciones y generar notificaciones internas.

### Estados de una factura

Actualmente el sistema contempla los siguientes estados:

- recibida
- por_pagar
- en_revision
- correccion
- confirmada

Cada estado representa una etapa dentro del proceso de gestión.

#### Recibida

La factura fue registrada en el sistema.

#### Por pagar

La factura fue asignada y se encuentra pendiente de pago.

#### En revisión

El usuario realizó el proceso correspondiente y subió un comprobante que debe ser revisado.

#### Corrección

El comprobante o información requiere una modificación antes de poder confirmar el proceso.

#### Confirmada

El comprobante fue revisado y el pago de la factura quedó confirmado.

### Funcionalidades incorporadas en v0.3

La tercera etapa agrega:

- Registro de proveedor.
- Registro de folio.
- Asignación de facturas a áreas.
- Cambio de estados.
- Gestión del estado por_pagar.
- Carga de comprobantes de pago.
- Revisión de comprobantes.
- Solicitud de correcciones.
- Confirmación del pago.
- Historial de acciones.
- Registro de eventos.
- Notificaciones internas.
- Control de versiones de formularios.
- Edición de información de facturas.
- Eliminación recuperable.
- Papelera.
- Restauración de facturas.
- Conservación de documentos asociados.
- Búsqueda de facturas.
- Filtros por estado.

### Nuevos componentes de la Parte 3

Entre los componentes incorporados se encuentran:

- WorkflowController.php
- Aviso.php
- Documento.php
- Evento.php

También se agregan nuevas vistas relacionadas con el seguimiento:

- resources/views/facturas/history.blade.php
- resources/views/facturas/notifications.blade.php
- resources/views/facturas/pagination.blade.php
- resources/views/facturas/show.blade.php

Además, se modifican componentes existentes:

- FacturaController.php
- Factura.php
- routes/web.php
- resources/views/facturas/index.blade.php
- resources/views/layouts/app.blade.php

---

# Migraciones del proyecto

El proyecto actualmente contiene tres migraciones principales:

1. 2026_09_28_000001_create_facturas_table.php
2. 2026_09_28_000002_add_roles_and_areas.php
3. 2026_09_28_000003_add_invoice_workflow.php

## Migración 1

Crea la estructura inicial para el registro de facturas.

Archivo:

2026_09_28_000001_create_facturas_table.php

## Migración 2

Incorpora los elementos relacionados con usuarios, roles y áreas.

Archivo:

2026_09_28_000002_add_roles_and_areas.php

## Migración 3

Agrega los elementos necesarios para implementar el workflow de seguimiento.

Archivo:

2026_09_28_000003_add_invoice_workflow.php

Esta etapa incorpora, entre otros elementos, las tablas:

- documentos
- eventos
- avisos

---

# Flujo general del sistema

Factura registrada
        ↓
     Recibida
        ↓
Asignación a un área
        ↓
    Por pagar
        ↓
Usuario realiza el proceso de pago
        ↓
Sube comprobante
        ↓
   En revisión
        ↓
┌───────────────┴─────────────────┐
│                                 │
Corrección requerida       Comprobante aprobado
│                                 │
↓                                 ↓
Corrección                 Pago confirmado
│                                 │
↓                                 ↓
Nuevo comprobante             Confirmada
│
└──────────────→ En revisión

---

# Historial y trazabilidad

El sistema registra información relacionada con los cambios realizados durante el proceso de una factura.

Esto permite mantener evidencia de acciones como:

- Registro de factura.
- Asignación.
- Cambio de estado.
- Carga de documentos.
- Carga de comprobantes.
- Solicitud de corrección.
- Confirmación del pago.

Los eventos permiten mantener una trazabilidad básica del proceso realizado dentro de la plataforma.

---

# Documentos

El sistema permite mantener documentos asociados a las facturas.

Entre ellos se pueden encontrar:

- Factura original en PDF.
- Comprobantes de pago.
- Documentos asociados al proceso.

Los documentos no se almacenan directamente dentro de la base de datos. El sistema almacena la información necesaria para localizar los archivos correspondientes.

---

# Notificaciones

La versión v0.3 incorpora avisos internos relacionados con acciones realizadas sobre las facturas.

Estos avisos permiten informar al usuario cuando ocurre un cambio relevante dentro del flujo.

---

# Papelera y restauración

Las facturas pueden ser eliminadas de forma recuperable.

El flujo general es:

Factura
   ↓
Eliminación
   ↓
Papelera
   ↓
Restauración

De esta forma se evita perder inmediatamente la información relacionada con una factura.

---

# Control de versiones

El sistema incorpora un mecanismo de control de versión de los registros.

Su objetivo es disminuir problemas cuando dos acciones se realizan utilizando información desactualizada.

Ejemplo:

Usuario abre factura
        ↓
Otro usuario modifica la factura
        ↓
Primer usuario intenta enviar formulario antiguo
        ↓
El sistema puede detectar que la información cambió

---

# Arquitectura general

De forma simplificada, el proyecto utiliza la estructura MVC de Laravel.

Usuario
   ↓
Routes
   ↓
Controllers
   ↓
Models
   ↓
Base de datos
   ↓
Views Blade
   ↓
Usuario

Entre los principales controladores se encuentran:

- FacturaController
- AuthController
- WorkflowController

Entre los modelos principales se encuentran:

- Factura
- User
- Area
- Documento
- Evento
- Aviso

---

# Instalación

## 1. Clonar el repositorio

git clone https://github.com/TheBlackSoldier1/sistema-facturas.git

## 2. Entrar al proyecto

cd sistema-facturas

## 3. Instalar dependencias

composer install

## 4. Crear archivo de configuración

cp .env.example .env

## 5. Generar clave de Laravel

php artisan key:generate

## 6. Crear base SQLite

touch database/database.sqlite

## 7. Ejecutar migraciones y datos iniciales

php artisan migrate --seed

## 8. Iniciar servidor

php artisan serve

Laravel normalmente iniciará el proyecto en:

http://127.0.0.1:8000

---

# Archivos que no deben subirse a GitHub

Por seguridad y para evitar archivos innecesarios dentro del repositorio, no se deben subir:

- .env
- vendor/
- node_modules/
- database/database.sqlite

El archivo .env puede contener configuraciones locales o información sensible.

La carpeta vendor/ se reconstruye mediante:

composer install

La base database/database.sqlite corresponde a información local y puede volver a crearse utilizando las migraciones.

El archivo .gitignore del proyecto ya excluye estos elementos.

---

# Historial de versiones

## v0.1 — Parte 1

Registro y almacenamiento básico de facturas.

Se implementó:

- Estructura inicial del proyecto.
- Registro de facturas.
- Carga de PDF.
- Almacenamiento.
- Listado.
- Descarga de documentos.

## v0.2 — Parte 2

Autenticación, roles y áreas.

Se agregó:

- Inicio de sesión.
- Usuarios.
- Roles.
- Áreas.
- Asociación entre usuarios y áreas.
- Adaptación del registro de facturas a la nueva estructura.

## v0.3 — Parte 3

Workflow de seguimiento de facturas.

Se agregó:

- Estados de factura.
- Asignación.
- Comprobantes.
- Revisión.
- Solicitud de corrección.
- Confirmación.
- Historial.
- Eventos.
- Notificaciones.
- Edición.
- Papelera.
- Restauración.
- Filtros.
- Búsqueda.
- Control de versiones.

---

# Estado del proyecto

La versión actual es:

v0.3

El proyecto continúa en desarrollo, por lo que pueden incorporarse nuevas funcionalidades, ajustes y pruebas en versiones posteriores.

Las siguientes versiones podrían continuar como:

v0.4
v0.5
v0.6

La versión v1.0 se reservará para una versión considerada estable y preparada para su utilización final.
