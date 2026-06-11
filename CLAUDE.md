# ProjectMSP - Agent Skills Workflow

## Stack
- **Backend**: PHP 8.3 + Laravel 13
- **Frontend**: Tailwind CSS, Blade, Alpine.js
- **Container**: Docker (app + MySQL 8.0 + Redis)
- **PDFs**: Spatie/Browsershot (Chromium)
- **Queue**: Supervisor + Laravel Queue
- **External APIs**: Microsoft Graph, SendGrid, Odoo, Anthropic Claude, MSP API, Meraki API, GLPI API

## Módulos del Sistema

### Módulos activos (config/modules.php)
| Slug | Nombre | Descripción |
|---|---|---|
| `usuarios` | Usuarios y Roles | CRUD usuarios + sistema de roles dinámico con módulos |
| `msp_reports` | MSP Reports | Importar Excel, generar PDFs, envío masivo, chat IA |
| `meta2` | Meta 2 | Tickets de Telefonía con SSE streaming y exportación |
| `encuestas` | Encuestas | Tipos de encuesta, respuestas, tokens API |
| `api_msp` | API MSP | Consulta tickets por fecha con SSE, exportar Excel, chat IA |
| `glpi` | GLPI | Inventario de activos IT (computadoras, red, impresoras, etc.) |
| `sales` | Ventas | Dashboard Odoo, pipeline, ejecutivas, comisiones, reasignación |
| `meraki` | Meraki | Dispositivos Cisco Meraki, licencias, alertas, organizaciones |

---

## Módulo GLPI (`app/Services/GlpiService.php`)

### Qué hace
Conecta con la API REST de GLPI para gestionar inventario de activos IT.

### Rutas
| Método | URI | Descripción |
|---|---|---|
| GET | `/admin/glpi/` | Dashboard con resumen por tipo de activo |
| GET | `/admin/glpi/{itemtype}` | Listado agrupado por tipo+modelo. `search`, `sort` |
| GET | `/admin/glpi/{itemtype}/{id}` | Detalle de activo |
| POST | `/admin/glpi/{itemtype}` | Crear activo |
| PUT | `/admin/glpi/{itemtype}/{id}` | Actualizar activo |
| POST | `/admin/glpi/session/init` | Iniciar sesión GLPI |
| POST | `/admin/glpi/session/kill` | Cerrar sesión GLPI |
| POST | `/admin/glpi/cache/refresh` | Refrescar caché desde UI |

### Variables de entorno requeridas
```
GLPI_BASE_URL=https://glpi.tudominio.com/apirest.php
GLPI_APP_TOKEN=
GLPI_USER_TOKEN=
```

### Caché
- Items/inventario: **24 horas** — `glpi_items_{itemtype}_{md5}`
- Session token: **50 min** — `glpi_session_token`

### Comando warm-cache
```bash
php artisan glpi:warm-cache
```
Scheduler: cada 30 minutos automáticamente.

---

## Módulo Meraki (`app/Services/MerakiService.php`)

### Qué hace
Conecta con la API de Cisco Meraki para monitorear dispositivos de red, licencias y alertas.

### Rutas
| Método | URI | Descripción |
|---|---|---|
| GET | `/admin/meraki/` | Dashboard global — cards por modelo, donut, bar chart |
| GET | `/admin/meraki/licenses` | Licencias agrupadas por modelo con panel de detalle |
| GET | `/admin/meraki/alerts` | Central de alertas — dispositivos offline/alerting |
| GET | `/admin/meraki/models/{model}` | Detalle de modelo: dispositivos + licencias |
| GET | `/admin/meraki/{orgId}` | Detalle de organización: redes, uplinks, dispositivos |
| GET | `/admin/meraki/{orgId}/networks/{networkId}` | Detalle de red: SSIDs, eventos, alertas, clientes |
| GET | `/admin/meraki/export/devices` | Exportar dispositivos a Excel (.xlsx). Acepta `?org={orgId}` para filtrar por organización |
| GET | `/admin/meraki/export/licenses` | Exportar licencias a Excel (.xlsx) |
| GET | `/admin/meraki/export/alerts` | Exportar dispositivos con alertas (offline/alerting) a Excel (.xlsx) |
| POST | `/admin/meraki/refresh-all` | Limpiar caché global |
| POST | `/admin/meraki/{orgId}/refresh` | Limpiar caché de organización |
| POST | `/admin/meraki/{orgId}/networks/{networkId}/refresh` | Limpiar caché de red |

> Todas las vistas del módulo incluyen filtros client-side (búsqueda + estado/categoría) con Alpine.js, sin recargar la página.

### Variables de entorno requeridas
```
MERAKI_API_KEY=
MERAKI_BASE_URL=https://api.meraki.com/api/v1
```

### Endpoints API Meraki consumidos
| Endpoint | TTL caché |
|---|---|
| `GET /organizations` | 24h |
| `GET /organizations/{orgId}/networks` | 24h |
| `GET /organizations/{orgId}/devices` | 24h |
| `GET /organizations/{orgId}/devices/statuses` | 5 min |
| `GET /organizations/{orgId}/uplinks/statuses` | 5 min |
| `GET /organizations/{orgId}/licenses` | 48h |
| `GET /networks/{networkId}/devices` | 24h |
| `GET /networks/{networkId}/clients/overview` | 3 min |
| `GET /networks/{networkId}/wireless/ssids` | 24h |
| `GET /networks/{networkId}/events` | 5 min |
| `GET /networks/{networkId}/health/alerts` | 5 min |

### Notas importantes
- Organizaciones con **co-termination licensing** (no per-device) devuelven `[]` en licencias sin lanzar excepción — se loguea como info.
- El caché de todos los dispositivos es **global** (`meraki_all_devices_global`) — compartido entre todos los usuarios, no por sesión.
- Los datos volátiles (statuses, uplinks, eventos, alertas, clients overview) usan **`Cache::flexible`** (stale-while-revalidate): frescos 3–5 min, servibles hasta 15–30 min mientras se regeneran en segundo plano.
- Las peticiones HTTP tienen **reintentos automáticos** (429 honrando `Retry-After`, 5xx con backoff) y los endpoints de listado **paginan** por cabecera `Link` con `perPage=1000`.

### Comando warm-cache
```bash
php artisan meraki:warm-cache
```
Scheduler: cada 30 minutos automáticamente.

---

## Autenticación

### Login normal
Email + password → 2FA (Google Authenticator) → Dashboard

### Microsoft SSO (`app/Http/Controllers/Auth/MicrosoftAuthController.php`)
Login con cuenta Microsoft Azure AD. **Solo usuarios ya registrados en el sistema** pueden entrar.

**Flujo:**
1. Clic en "Entrar con Microsoft" → redirige a Microsoft
2. Microsoft autentica y regresa a `/auth/microsoft/callback`
3. Sistema busca el email en tabla `users` — si no existe, rechaza
4. Si existe → login directo, **sin 2FA** (Microsoft ya autenticó)

**Rutas:**
```
GET /auth/microsoft           → redirige a Microsoft
GET /auth/microsoft/callback  → callback tras autenticación
```

**Paquetes:**
```
laravel/socialite
socialiteproviders/microsoft-azure
```

**Variables de entorno (ya existen para SharePoint):**
```
AZURE_TENANT_ID=
AZURE_CLIENT_ID=
AZURE_CLIENT_SECRET=
```

**Redirect URI en Azure Portal:**
```
https://analytics.ovni.com/auth/microsoft/callback
```
> Configurado en: Azure Portal → App registrations → tu app → Authentication

---

## Caché — TTLs globales

| Servicio | Dato | TTL |
|---|---|---|
| GLPI | Items/inventario | 24h |
| GLPI | Session token | 50 min |
| Meraki | Inventario (orgs, redes, devices) | 24h |
| Meraki | Statuses online/offline | 5 min |
| Meraki | Licencias | 48h |
| Meraki | Todos los devices (global) | 24h |
| Meta2 | IDs tickets del mes | 24h |
| Meta2 | Detalle de tickets | 24h |
| Meta2 | Custom fields por ticket | 48h |
| Meta2 | PDF del informe | 48h |
| MSP | Custom fields por ticket | 48h |
| Odoo | KPIs, Pipeline, Clientes | 24h |
| Odoo | Ejecutivas | 24h |
| Odoo | Comisiones | 48h |
| SharePoint | Token OAuth | ~58 min |

---

## Comandos Artisan

```bash
php artisan glpi:warm-cache       # Pre-carga inventario GLPI
php artisan meraki:warm-cache     # Pre-carga devices y licencias Meraki
php artisan sales:refresh-cache   # Refresh caché de ventas (diario 06:00)
```

### Scheduler (routes/console.php)
```php
Schedule::command('sales:refresh-cache')->dailyAt('06:00');
Schedule::command('glpi:warm-cache')->everyThirtyMinutes();
Schedule::command('meraki:warm-cache')->everyThirtyMinutes();
```

---

## Agent Skills Disponibles

### 1. /spec - Crear Especificaciones
**Cuándo**: Antes de desarrollar una feature nueva
**Entrada**: claude "/spec: [descripción de la feature]"
**Output**: Documento con reqs, arquitectura, modelos, rutas, seguridad

### 2. /plan - Descomponer Tareas
**Cuándo**: Después de spec aprobada
**Entrada**: claude "/plan: Descomponer [feature]"
**Output**: Roadmap atomizado en fases, dependencias, estimaciones

### 3. /build - Implementar Incrementalmente
**Cuándo**: Listo para escribir código
**Entrada**: claude "/build: [tarea específica del plan]"
**Output**: Código PHP/Blade/JavaScript listos para copiar-pegar + instrucciones Docker

### 4. /test - Tests Automatizados
**Cuándo**: Después de cada /build
**Entrada**: claude "/test: [feature/component]"
**Output**: Tests completos (Feature + Unit) con >80% coverage

### 5. /review - Code Review Estructurado
**Cuándo**: Antes de merge a main
**Entrada**: claude "/review: [archivos afectados]"
**Output**: Checklist de código, recomendaciones seguridad, performance gaps

### 6. /ship - Deploy & Release
**Cuándo**: Después de /review aprobado
**Entrada**: claude "/ship: [feature] a analytics.ovni.com"
**Output**: Pasos de deploy exactos, pre/post validaciones, plan rollback

---

## Flujo Completo
spec → plan → [build → test]* → review → ship

## Principios Transversales
- **Docker is source of truth**: Paths Linux, .env vars, Supervisor
- **Seguridad primero**: Type hints, RBAC, FormRequest validation
- **Data normalization at source**: No parches en views
- **Logs & monitoring**: Log::info/error con contexto
- **Test coverage >80%**: Happy path + validations + permisos + edge cases

## Referencias Rápidas
- Modelos existentes: Dashboard, Users, MSP Reports, META 2, Surveys, Sales, API Customers, Client Merge, GLPI, Meraki
- Production domain: analytics.ovni.com (CentOS VM via Termius SSH)
- Local dev: localhost:8080 (Docker)
- Integrations: SharePoint (Azure AD), SendGrid, Odoo, Claude AI, MSP API, Meraki API, GLPI API, Microsoft SSO

## Deploy en Producción
```bash
cd /var/www/projectMSP
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force       # solo si hay migraciones nuevas
php artisan glpi:warm-cache
php artisan meraki:warm-cache
```
