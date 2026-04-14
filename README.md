# 📡 projectMSP

Sistema de gestión interno construido con **Laravel 13 + Tailwind CSS**, orientado a la administración de tickets, reportes, encuestas, clientes y comunicaciones de una empresa de telecomunicaciones/MSP.

---

## 🛠 Stack Tecnológico

| Tecnología | Versión | Uso |
|---|---|---|
| PHP | ^8.3 | Backend |
| Laravel | ^13.0 | Framework principal |
| Laravel Breeze | ^2.0 | Autenticación |
| Laravel Sanctum | ^4.0 | API tokens |
| Laravel AI | ^0.4.0 | Chat con IA integrado |
| Maatwebsite/Excel | ^3.1 | Importación y exportación Excel |
| Spatie/Browsershot | ^5.2 | Generación de PDFs via headless Chrome |
| Tailwind CSS | — | Estilos (dark mode incluido) |
| Vite | — | Bundler de assets |

---

## 🚀 Instalación

```bash
# 1. Clonar el repositorio
git clone <url-del-repo>
cd projectMSP

# 2. Setup automático (instala dependencias, genera key, migra y compila)
composer run setup

# 3. Iniciar el entorno de desarrollo
composer run dev
```

El comando `dev` levanta en paralelo:
- `php artisan serve` — servidor PHP
- `php artisan queue:listen` — procesamiento de colas
- `php artisan pail` — logs en tiempo real
- `npm run dev` — Vite con hot reload

---

## 📁 Módulos del Sistema

### 1. 🏠 Dashboard
**Ruta:** `/dashboard`

Panel principal con accesos directos a todos los módulos. Muestra KPIs generales y navegación rápida con tarjetas hacia Ventas, Clientes, Ejecutivas y Reasignación.

---

### 2. 👥 Usuarios
**Ruta:** `/admin/users`  
**Controlador:** `App\Http\Controllers\Admin\UserController`

CRUD completo de usuarios del sistema. Funciones:
- Listar usuarios con paginación
- Crear usuario con rol asignado
- Editar nombre, email y rol
- Eliminar usuario (no puede eliminarse a sí mismo)

**Roles disponibles:** `admin`, `editor`, `user`, `ventas`, entre otros...

---

### 3. 📋 Reportes MSP (Masivos)
**Ruta:** `/admin/reports/msp`

Módulo de reportes para clientes importados desde Excel. Funciones:
- **Importar Excel** — carga masiva de clientes con toda la información del archivo
- **Ventana PDF** — dashboard con gráficas (pie chart de clasificación de incidentes) y tablas por cliente; generado con Browsershot
- **Ventana de clientes** — visualización de la información de cada cliente
- **Ventana de correos** — envío de correos masivos o individuales con SendGrid, soporta plantillas con variables dinámicas (`[[periodo]]`, `[[nombre]]`, etc.)
- **Chat IA** — asistente integrado que puede consultar datos, generar PDFs y enviarlos por correo

**Tablas:** `msp_imports`, `msp_clients`, `msp_plantillas`

---

### 4. 🔌 API MSP
**Ruta:** `/admin/api-msp`  
**Controlador:** `App\Http\Controllers\Admin\ApiMspController`

Conecta con la API externa de MSP Manager para consultar tickets. Funciones:
- **Guardar credenciales** — username, password y base URL de la API
- **Consultar tickets** por rango de fechas
- **Exportar a Excel** los tickets obtenidos
- **Chat IA con SSE** — streaming de respuestas para consultas sobre los tickets

**Modelo:** `MspCredential` (una sola credencial activa, se trunca al guardar)

---

### 5. 📞 META 2
**Ruta:** `/admin/meta-2`  
**Controlador:** `App\Http\Controllers\Admin\Meta2Controller`  
**Servicio:** `App\Services\Meta2Service`

Módulo de tickets de telefonía que consume la API de MSP Manager. Funciones:
- Listar tickets con búsqueda y filtros por mes/año
- Paginación manual con `LengthAwarePaginator`
- **Modal de detalle** con campos dinámicos configurables
- **Exportar a Excel**
- **Exportar a PDF** con Browsershot
- **Stream de IA (SSE)** para consultas en tiempo real

Campos dinámicos configurados en `$requiredFieldIds` del servicio:
- Causa, Ubicación, Detalle Reporte 2, Reporte 1, Provincia, Telefonía, Solución

---

### 6. 📊 Encuestas
**Ruta:** `/admin/surveys`  
**Controladores:** `SurveyTypeController`, `SurveyController`  
**API:** `App\Http\Controllers\Api\SurveyApiController`

Sistema autogestionable de encuestas de satisfacción. Funciones:
- **Crear tipos de encuesta** con campos personalizados dinámicos
- **Auto-generación de token/slug** por encuesta para integración con bot de WhatsApp
- **Recepción de respuestas** vía API (`POST /api/surveys/{token}`) autenticada con Sanctum
- **Ver respuestas** por tipo de encuesta
- **Exportar a Excel** con formato estilizado

**Tablas:** `survey_types`, `surveys`

---

### 7. 💼 Ventas
**Ruta:** `/admin/sales`

Módulo de seguimiento comercial con datos de Odoo. Sub-secciones:
- **Dashboard** — KPIs y métricas generales de ventas
- **Clientes** — actividad y riesgo por cuenta
- **Ejecutivas** — métricas individuales por ejecutiva de ventas
- **Reasignación** — gestión de cuentas en riesgo (`$kpis['atRisk']`)

---

### 8. 🔗 API Customers
**Ruta:** `/admin/api-customers`  
**Controlador:** `App\Http\Controllers\Admin\ApiCustomersController`

Consulta la misma API de MSP Manager para obtener el listado de customers (clientes). Funciones:
- Reutiliza las credenciales guardadas en el módulo API MSP
- Muestra los clientes en tabla
- **Exportar a Excel**

---

### 9. 🔀 Client Merge (MSP + ODOO)
**Ruta:** `/admin/client-merge`

Herramienta para combinar clientes de MSP con clientes de ODOO usando similitud de nombres (fuzzy matching). Funciones:
- Subir dos archivos Excel: `MSP_CLIENTS.xlsx` y `ODOO_CLIENTS.xlsx`
- Ajustar el umbral de similitud (50%–100%) con slider
- Algoritmo de matching por nombre (limpia sufijos como `NOMBRE - 04001090`)
- Si un cliente MSP tiene varios matches en ODOO, agrupa los `NumeroCuenta` y `RUC` separados por `|`
- **Descarga Excel** con columnas: `CustomerID_MSP`, `CustomerName_MSP`, `NumeroCuenta`, `RUC`
- Clientes sin match se incluyen con campos vacíos

---

## 🗄 Migraciones Principales

| Migración | Descripción |
|---|---|
| `create_msp_credentials_table` | Credenciales de acceso a la API de MSP |
| `create_msp_imports_table` | Historial de importaciones de Excel |
| `create_msp_clients_table` | Clientes importados vía Excel |
| `create_survey_types_table` | Tipos/templates de encuestas |
| `create_surveys_table` | Respuestas de encuestas |
| `create_msp_plantillas_table` | Plantillas de correo personalizadas |
| `create_personal_access_tokens_table` | Tokens Sanctum para API de encuestas |

---

## 🎨 Características Globales

- **Dark mode** con toggle persistente via `localStorage` (default: modo claro)
- **Favicon y branding Ovnicom** en navbar y tabs del navegador
- **Tailwind CSS** con `darkMode: 'class'` configurado
- **IIFE en JS** para evitar conflictos de variables globales entre módulos
- **Vistas Blade** con layouts `x-app-layout` y componentes reutilizables

---

## 🔐 Autenticación y Roles

Basado en **Laravel Breeze** con campo `role` en la tabla `users`:

| Rol | Acceso |
|---|---|
| `admin` | Acceso completo a todos los módulos |
| `editor` | Módulos de consulta y reportes |
| `ventas` | Módulo de Ventas |
| `user` | Acceso básico |

---

## 🧰 Comandos Útiles

```bash
# Migraciones
php artisan migrate
php artisan migrate:fresh --seed

# Limpiar caché
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Ver rutas del admin
php artisan route:list | grep admin

# Compilar assets (producción)
npm run build

# Ver logs (Windows)
type storage\logs\laravel.log
```

---

## 📌 Notas para Producción

- Ejecutar `php artisan storage:link` para acceso público a imágenes
- El logo para PDFs debe existir en `storage/app/public/logos/ovni.png`
- Las imágenes/banner de correos funcionan con URL pública (no requiere base64)
- La tabla `msp_plantillas` debe existir (`php artisan migrate`)
- Configurar variables de entorno: `SENDGRID_API_KEY`, credenciales de MSP Manager

---

## 📂 Estructura de Carpetas Relevante

```
app/
├── Http/Controllers/Admin/
│   ├── ApiMspController.php
│   ├── ApiCustomersController.php
│   ├── Meta2Controller.php
│   ├── ReportsMspController.php
│   ├── SurveyTypeController.php
│   ├── SurveyController.php
│   ├── UserController.php
│   └── ClientMergeController.php
├── Services/
│   ├── MspService.php
│   └── Meta2Service.php
├── Models/
│   ├── MspCredential.php
│   ├── MspClient.php
│   ├── SurveyType.php
│   └── Survey.php
└── Exports/
    ├── ApiMspExport.php
    └── SurveyExport.php

resources/views/admin/
├── api-msp/
├── api-customers/
├── meta-2/
├── reports/msp/
├── surveys/
├── sales/
├── users/
└── client-merge/
```