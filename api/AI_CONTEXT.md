# Intermedius API — Contexto Completo para IA

> Documentación jerárquica del backend Laravel. Cada subdirectorio tiene su propio `AI_CONTEXT.md` con detalle profundo.

---

## 1. Stack Tecnológico

| Componente | Tecnología |
|---|---|
| Framework | Laravel 11 |
| PHP | 8.2+ |
| Base de datos | MySQL 8 |
| Auth | Laravel Sanctum (Bearer tokens) |
| Roles/Permisos | Spatie Laravel Permission |
| Queue | Redis + Laravel Horizon |
| PDF | barryvdh/laravel-dompdf |
| Excel | maatwebsite/laravel-excel (Laravel Excel 3.1) |
| Activity Log | spatie/laravel-activitylog |
| Frontend | Vue 3 + Pinia + Vite (repo separado en `frontend/`) |

---

## 2. Arquitectura General

```
api/                           → Backend Laravel
├── app/
│   ├── Exports/               → Exportaciones Excel (comisiones)
│   ├── Http/
│   │   ├── Controllers/       → Controladores (20 archivos)
│   │   ├── Requests/          → Form Request validation (22 archivos)
│   │   └── Resources/         → Transformación JSON (2 resources)
│   ├── Jobs/                  → Jobs asíncronos (7 jobs)
│   ├── Models/                → Eloquent ORM (16 modelos)
│   ├── Policies/              → Autorización (7 policies)
│   ├── Providers/             → Service providers (2)
│   └── Services/              → Lógica de negocio (5 servicios)
├── config/                    → 20 archivos de configuración
├── database/
│   ├── schema/mysql.sql       → Schema dump (sin migraciones)
│   └── seeders/               → 3 seeders de datos iniciales
├── docs/api-reference.md      → Documentación de endpoints
├── routes/
│   ├── api.php                → ~89 rutas API (prefix /api/v1)
│   └── console.php            → 7 jobs scheduleados
└── tests/                     → PHPUnit tests

frontend/                      → Frontend Vue 3 (SPA separado)
```

### Flujo de datos

```
Cliente (Vue SPA)
    → HTTP Request (axios)
    → api.php (routes) → Middleware (auth:sanctum, role, bindings)
    → Controller → authorize(Policy) → FormRequest(rules)
    → Service (business logic) → Model (Eloquent)
    → Response JSON (Resources)
    → Cliente (Vue SPA renderiza)
```

---

## 3. Base de Datos

Ver `api/database/AI_CONTEXT.md` para detalle completo de schema, foreign keys, índices y seeders.

### Tablas principales (31 total)

| Grupo | Tablas |
|---|---|
| **Catálogos** | `bancos`, `monedas`, `tipos_operacion`, `categorias_gasto`, `titulares` |
| **Core negocio** | `clientes`, `cuentas`, `operaciones`, `movimientos` |
| **Tasas** | `tasas_diarias`, `tasas_mercado` |
| **Comisiones** | `comisiones_cuenta`, `comisiones_metodo_pago`, `comisiones_operador`, `comisiones_operacion` |
| **Auth** | `users`, `personal_access_tokens`, `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` |
| **Soporte** | `activity_log`, `failed_jobs`, `jobs`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `notifications` |

### Relaciones clave

```
Clientes 1──N Cuentas
Titulares 1──N Cuentas
Cuentas N──1 Bancos
Cuentas N──1 Monedas
Operaciones N──1 Clientes
Operaciones N──1 TiposOperacion
Operaciones 1──N Movimientos
Movimientos N──1 Cuentas
Movimientos N──1 Monedas
TasasDiarias N──1 Monedas (base + cotizada)
ComisionOperador N──1 Titulares
ComisionOperacion N──1 Operaciones
```

### Seeders

| Seeder | Datos que crea |
|---|---|
| `AdminUserSeeder` | Usuario admin + super_admin inicial |
| `CatalogosBaseSeeder` | Roles (6), monedas (5: USD, VES, USDT, EUR, COP), bancos (13 venezolanos), tipos_operación (8: depósito, retiro, compra_usd, venta_usd, intermediada, etc.), categorías_gasto, titular "Pago a terceros", tasas de operador por defecto |
| `DatabaseSeeder` | Orquesta ambos |

---

## 4. Rutas API

Ver `api/routes/AI_CONTEXT.md` para lista completa de las ~89 rutas.

### Resumen por grupo

| Grupo | Prefix | Rutas |
|---|---|---|
| Autenticación | `/auth` | login (pública), logout, me |
| Catálogos CRUD | `/titulares`, `/bancos`, `/monedas`, `/cuentas`, `/clientes`, `/categorias-gasto` | 6 apiResource c/u |
| Clientes extra | `/clientes/{id}/cuentas`, `/clientes/{id}/operaciones`, `/clientes/{id}/operaciones/exportar`, `/clientes/{id}/restaurar` | 4 rutas adicionales |
| Cuentas extra | `/cuentas/{cuenta}/saldo` | 1 ruta |
| Tasas mercado | `/tasas/actuales`, `/tasas/historico` | 2 rutas |
| Operaciones | `/operaciones` | CRUD + verificar (bind `{operacion}`) |
| Pool pagadores | `/pool` | index, mis-ordenes, tomar, soltar, pagar, cancelar |
| Gastos | `/gastos` | CRUD |
| Dashboard | `/dashboard/general`, `/dashboard/tasas-referencia`, `/dashboard/resumen` | 3 rutas |
| Configuración | `/configuracion/` | tasas-diarias API, 4 comisiones API |
| Reportes | `/reportes/comisiones` | index, exportar, historico |
| Usuarios | `/users` | CRUD (solo admin/super_admin) |
| Bitácora | `/bitacora` | activity log (solo super_admin) |

### Roles y permisos

| Rol | Acceso |
|---|---|
| `super_admin` | Todo (Gate::before) |
| `admin` | CRUD completo, verificar operaciones, pool, configuración |
| `operador` | CRUD operaciones, pool, vista catálogos |
| `contador` | Reportes, gastos, vista catálogos |
| `lectura` | Solo vista de todo |
| `pagador` | Pool (tomar, soltar, pagar) |

---

## 5. Modelos

Ver `api/app/Models/AI_CONTEXT.md` para detalle completo de los 16 modelos con fillable, casts y relaciones.

### Soft Deletes

- `Cliente` — soft-delete (`deleted_at` nullable). Restore via `POST /clientes/{id}/restaurar` (policy `restore`). Filtro `?inactivos=true` en index para ver eliminados.
- `Operacion` — soft-delete para cancelación lógica.

### Activity Log (spatie)

Modelos con `$recordEvents`: `Cliente`, `Cuenta`, `Operacion`, `Movimiento`, `ComisionOperacion`.

---

## 6. Controladores

Ver `api/app/Http/Controllers/AI_CONTEXT.md` para detalle completo de los 20 controladores.

### Convenciones

- Todo método público inicia con `$this->authorize()`
- Respuestas: `Illuminate\Http\JsonResponse`
- Paginación: `Model::query()->...->paginate(50)`
- Filtros: `when($request->filled('q'), ...)`, `when($request->boolean('inactivos'), ...)`
- Route Model Binding con scope bindings en algunos casos (PoolController usa `operacion:pool_pendiente`)

---

## 7. Form Requests (Validación)

Ver `api/app/Http/Requests/AI_CONTEXT.md` para reglas completas de los 22 requests.

### Convenciones

- `authorize()` delega en Policy del modelo
- `Store{Entidad}Request` para creación, `Update{Entidad}Request` para actualización
- Unique compuestos: `Cuenta.alias` único por owner (titular_id o cliente_id)
- `required_without` para mutua exclusión: `titular_id` / `cliente_id`
- `prepareForValidation()`: cuando `tipo === 'efectivo'` y no hay titular/cliente, asigna `titular_id = 1` (Terceros)
- `withValidator()` para lógica cross-campo (validar vigencia, verificar fondos, etc.)

---

## 8. Jobs (Cola)

Ver `api/app/Jobs/AI_CONTEXT.md`. Todos implementan `ShouldQueue`, procesados por Horizon (Redis).

| Job | Schedule | Descripción |
|---|---|---|
| `SincronizarTasasJob` | Cada 1 min | Tasas BCV, paralelo, Binance P2P |
| `SincronizarTasasReferenciaJob` | Cada 1 min | Tasas referencia adicionales |
| `AlertarTasasFaltantesJob` | 08:00 y 14:00 diario | Email a admins si faltan tasas |
| `GenerarReporteMensualComisionesJob` | 1ro del mes 06:00 | Reporte comisiones mensual |
| `ProcesarFifoOperacionJob` | On-demand | Procesamiento FIFO |
| `RecalcularSaldoCuentaJob` | On-demand | Recalcular saldo cuenta |
| `AutoArchivarClientesInactivos` | Dom 03:00 | Soft-delete clientes sin operaciones 4+ meses |

---

## 9. Policies

Ver `api/app/Policies/AI_CONTEXT.md`.

| Policy | Entidad | Acciones |
|---|---|---|
| `BancoPolicy` | Banco | CRUD |
| `CategoriaGastoPolicy` | CategoriaGasto | CRUD |
| `ClientePolicy` | Cliente | CRUD + `restore` |
| `CuentaPolicy` | Cuenta | CRUD + `cargarSaldo` |
| `MonedaPolicy` | Moneda | CRUD |
| `OperacionPolicy` | Operacion | CRUD + `verificar` |
| `TitularPolicy` | Titular | CRUD |

Regla general: `viewAny`/`view` → admin|operador|contador|lectura. `create`/`update`/`delete` → admin.

---

## 10. Servicios

| Servicio | Ubicación | Responsabilidad |
|---|---|---|
| `TasasMercadoService` | `Services/Tasas/` | Consultar tasas externas (BCV, paralelo, Binance P2P) |
| `TasaDiariaService` | `Services/Configuracion/` | Publicar/obtener/validar tasas diarias |
| `CalculadorComisionesService` | `Services/Configuracion/` | Calcular comisiones según reglas de negocio |
| `RegistroOperacionService` | `Services/Operaciones/` | Orquestar registro completo de operaciones |
| `ReporteComisionesOperadoresService` | `Services/Reportes/` | Generar reportes Excel/PDF de comisiones |

---

## 11. Reglas de Negocio Clave

### Cuentas
- Una cuenta pertenece EXCLUSIVAMENTE a un Titular O a un Cliente (nunca ambos)
- Tipo `efectivo` no requiere banco, número de cuenta ni titular explícito (auto-asigna Terceros)
- Alias único por owner (titular_id o cliente_id)

### Operaciones
- Tipos: `deposito`, `retiro`, `compra_usd`, `venta_usd`, `intermediada`
- FIFO para retiros: los fondos se asignan según orden de llegada
- Pool de pagadores: 5% de retiros van a pool, pagadores pueden tomarlos
- Soft-delete para cancelación lógica
- Verificación: solo admin/super_admin pueden verificar (marcar como completada)

### Tasas
- Tasas diarias publicadas manualmente por admin. `vigente_desde` / `vigente_hasta` para versionado
- Tasas de mercado sincronizadas automáticamente cada minuto de fuentes externas
- Alerta si faltan tasas del día (BCV, paralelo)

### Comisiones
- Configurables por: cuenta individual, método de pago, operador
- Tipos de comisión: `fijo`, `porcentaje`, `liquido`, `bruto`
- Vigencia con rango de fechas (`vigente_desde`, `vigente_hasta`)
- Se aplican automáticamente al registrar operación

### Clientes (T9)
- Soft-delete: `DELETE /clientes/{id}` solo marca `deleted_at`
- Restore: `POST /clientes/{id}/restaurar` (solo admin)
- Vista de inactivos: `GET /clientes?inactivos=true` trae solo eliminados
- Auto-archivo semanal: clientes sin operaciones en 4+ meses se eliminan automáticamente

---

## 12. Configuración del Sistema

Ver `api/config/AI_CONTEXT.md`.

| Archivo | Propósito |
|---|---|
| `sistema.php` | Pares principales, límites de búsqueda, meses de inactividad |
| `fifo.php` | Pool de pagadores (porcentaje, montos máximos) |
| `permission.php` | Roles y permisos Spatie |
| `reportes.php` | Configuración de generación de reportes |
| `horizon.php` | Workers, queues, balances |

---

## 13. Archivos por directorio

| Directorio | Archivo AI_CONTEXT |
|---|---|
| `app/` | `api/AI_CONTEXT.md` (este archivo) |
| `app/Models/` | `api/app/Models/AI_CONTEXT.md` |
| `app/Http/Controllers/` | `api/app/Http/Controllers/AI_CONTEXT.md` |
| `app/Http/Requests/` | `api/app/Http/Requests/AI_CONTEXT.md` |
| `app/Jobs/` | `api/app/Jobs/AI_CONTEXT.md` |
| `app/Policies/` | `api/app/Policies/AI_CONTEXT.md` |
| `app/Services/` | `api/app/Services/AI_CONTEXT.md` |
| `app/Exports/` | `api/app/Exports/AI_CONTEXT.md` |
| `app/Providers/` | `api/app/Providers/AI_CONTEXT.md` |
| `config/` | `api/config/AI_CONTEXT.md` |
| `routes/` | `api/routes/AI_CONTEXT.md` |
| `database/` | `api/database/AI_CONTEXT.md` |

---

## 14. Comandos Útiles

```bash
# Development
php artisan serve
php artisan queue:work
php artisan schedule:work

# Production
php artisan horizon
php artisan horizon:snapshot

# Testing
php artisan test
php artisan test --filter=Operacion

# Database
php artisan db:seed
php artisan db:seed --class=AdminUserSeeder
```

---

## 15. Frontend

El frontend es una SPA Vue 3 separada en `frontend/`.

- **State management**: Pinia stores en `frontend/src/stores/`
- **API client**: axios instance en `frontend/src/api/axios.js` con interceptor de token
- **Componentes**: reutilizables en `frontend/src/components/` (AppPageHeader, AppLoadingSpinner, AppErrorState, AppEmptyState, ClienteSelector)
- **Vistas**: en `frontend/src/views/` (ClientesView, CuentasView, OperacionesView, etc.)
- **Convención**: archivos en inglés, textos visibles en español
- **Formato**: moneda con `Intl.NumberFormat('en', ...)`, fechas con `toLocaleDateString('es-VE', ...)`
