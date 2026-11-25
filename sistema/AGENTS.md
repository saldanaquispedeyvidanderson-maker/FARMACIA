# Contexto del proyecto · Sistema de Venta de Medicamentos (Farmacia San Juan Bautista)
 
> Este archivo es para el asistente de IA (GitHub Copilot u otro). Léelo completo antes de proponer código.
> Si una instrucción del usuario contradice este archivo, pregunta antes de continuar.
 
## 1. Entorno (no cambiar)
 
- Laravel 13, PHP 8.3, MySQL (Laragon en Windows). El proyecto Laravel está en la carpeta `sistema/`.
- Bootstrap 5 y Bootstrap Icons ya están cargados en la plantilla. Chart.js estará en `public/js/chart.umd.min.js` (lo descarga el estudiante).
- NO instales paquetes, NO cambies la versión de PHP o Laravel, NO ejecutes `composer update`.
- NO uses CDN: el laboratorio puede no tener internet.
- NO ejecutes `migrate:fresh`, `db:wipe` ni comandos que borren datos sin pedir permiso antes.
 
## 2. Convenciones de código
 
- Nombres de tablas, columnas, modelos, controladores y rutas **en inglés**; textos de pantalla, comentarios y mensajes **en español**.
- Modelos con atributos de PHP: `#[Fillable([...])]` (no la propiedad `$fillable`). Relaciones con tipo de retorno (`BelongsTo`, `HasMany`).
- Validación con `$request->validate([...])`.
- Redirecciones con `to_route('modulo.index')->with('ok', 'Mensaje')` (verde) o `->with('error', 'Mensaje')` (rojo).
- Código comentado en español, explicando qué hace cada bloque (es un proyecto de enseñanza).
 
## 3. Vistas (Blade)
 
- Toda vista interna hereda la plantilla: `@extends('template.administrador')` (carpeta `template`, NO `layouts`).
- Secciones de la plantilla: `title`, `name_section`, `description`, `item_section`, `route_section`, `section`, `module`, `content`.
- Carpetas: `settings/` (Configuraciones), `management/` (Gestión), `principal/` (Dashboard y Reportes).
- Tarjeta: `card bg-white border-0 shadow-sm border-5 border-top border-secondary`.
- Mensajes: `@include('partials.alerts')`.
- JavaScript propio de una vista: `@push('scripts') ... @endpush` (la plantilla tiene `@stack('scripts')` al final).
- NUNCA escribas `@json` dentro de un comentario `//` de JavaScript: Blade lo procesa igual. Usa `{{-- --}}`.
- Menú lateral: `resources/views/partials/sidebar.blade.php`. Modal de cierre de sesión: `resources/views/partials/modal-logout.blade.php`.
 
## 4. Seguridad y roles (se implementa en la sesión 14)
 
- Hoy el sistema NO tiene login: cualquiera entra. La sesión 14 agrega inicio de sesión, roles y dashboard.
- El login es MANUAL: `LoginController` + `resources/views/auth/login.blade.php`. NO instales Breeze, Jetstream ni Fortify.
- `public/js/app.js` intercepta los elementos con `id="loginForm"` y `id="confirmLogoutBtn"`: NO uses esos `id`.
- Roles (tabla `roles`, nombres exactos con tilde): `Administrador`, `Químico farmacéutico`, `Cajero`.
- Permisos:
  - Todos los roles: dashboard, `customers`, `sales` (registrar y ver).
  - `Administrador` y `Químico farmacéutico`: `medicines` y reportes.
  - Solo `Administrador`: `categories`, `roles`, `users` y anular ventas (`sales.destroy`).
- Middleware propio `role` (clase `App\Http\Middleware\CheckRole`, alias en `bootstrap/app.php`) y método `User::hasRole(...$roles)`.
- TODA ruta interna va dentro de `Route::middleware('auth')->group(...)`. El login va en `Route::middleware('guest')`.
- El menú se oculta con `@if (auth()->user()->hasRole(...))`, y además la ruta se protege con el middleware: ocultar no es proteger.
- El vendedor de una venta es siempre `auth()->id()`; nunca se elige en un formulario.
 
## 5. Base de datos (lo que ya existe)
 
| Tabla | Columnas principales |
|---|---|
| roles | id, name, description |
| users | id, role_id, name, dni, phone, tuition, email, password, remember_token |
| categories | id, name, description |
| customers | id, name, dni, phone, age |
| medicines | id, category_id, name, price, stock, image |
| sales | id, customer_id, user_id, date, total, igv |
| detail_sales | id, sale_id, medicine_id, amount, price_unit, subtotal |
 
- La cantidad vendida es `amount` y el precio es `price_unit` (NO `quantity` ni `price` en `detail_sales`).
- El modelo `User` ya tiene la relación `role()` y el cast `'password' => 'hashed'`.
- Usuarios de prueba (seeders, contraseña `password`): `admin@sanjuan.com` (Administrador), `rquispe@sanjuan.com` (Químico farmacéutico), `cmendoza@sanjuan.com` y `ltorres@sanjuan.com` (Cajero).
- Para cambiar una tabla existente se crea una migración NUEVA; nunca se edita una migración ya ejecutada.
 
## 6. Idioma y hora
 
- `APP_LOCALE=es`, mensajes de validación en `lang/es/validation.php`. `timezone` = `America/Lima`.
 
## 7. Forma de trabajar
 
- Antes de modificar archivos, muestra un PLAN: qué archivos vas a crear o modificar y por qué. Espera mi confirmación.
- Haz solo lo que pide el prompt; no "mejores" otros archivos.
- Al terminar, explica en 5 líneas qué hiciste, cómo probarlo y qué podría fallar.
