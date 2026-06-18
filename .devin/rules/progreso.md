---
description: Contexto de progreso del proyecto Casa de Cambio – se actualiza en cada paso
alwaysApply: true
---

# Contexto de progreso — Intermedius ERP (Casa de Cambio)

## Estructura del monorepo
```
Intermedius ERP/
├── .windsurf/rules/
│   ├── casa-de-cambio.md   ← reglas del proyecto
│   └── progreso.md         ← este archivo
├── api/                    ← Laravel 11 backend
└── app/                    ← Flutter frontend
```

---

## Estado actual del backend (`api/`)

### Versiones
- Laravel: 11.51.0
- PHP: 8.3.30 (Laragon, `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64`)
- MySQL: 8.4.3 (Laragon, `C:\laragon\bin\mysql\mysql-8.4.3-winx64`)
- Puerto API: `http://localhost:8000`

### Dependencias instaladas
| Paquete | Versión | Nota |
|---|---|---|
| laravel/sanctum | ^4.3 | Auth SPA + tokens |
| spatie/laravel-permission | ^6.25 | Roles y permisos |
| spatie/laravel-activitylog | 4.9.0 | **v5 requiere PHP 8.4 — usar 4.9 siempre** |
| laravel/horizon | ^5.46 | Instalado con `--ignore-platform-req=ext-pcntl` (Windows no tiene pcntl) |

### Configs publicados
- `config/sanctum.php` — stateful domains incluyen `localhost:3000`, `localhost:8080`, `localhost:5000`
- `config/permission.php`
- `config/activitylog.php`
- `config/horizon.php`
- `config/cors.php` — creado manualmente (no existe por defecto en L11), permite orígenes Flutter dev con `supports_credentials: true`

### Bootstrap
- `bootstrap/app.php` — registra `HandleCors::class` global + `statefulApi()`

### Base de datos
- Nombre: `casa_cambio_dev`
- Charset: `utf8mb4_unicode_ci`
- Migraciones ejecutadas:
  - `create_users_table`
  - `create_cache_table`
  - `create_jobs_table`
  - `create_personal_access_tokens_table` (Sanctum)
  - `create_permission_tables` (Spatie)
  - `create_activity_log_table` (Spatie)
  - `add_event_column_to_activity_log_table`
  - `add_batch_uuid_column_to_activity_log_table`

### Estructura de carpetas creadas
```
app/
├── Services/
│   ├── Operaciones/
│   ├── Tasas/
│   ├── Fifo/
│   ├── Comisiones/
│   └── Reportes/
├── Jobs/
└── Http/Requests/
    ├── Cuenta/
    ├── Cliente/
    ├── Operacion/
    └── Gasto/
```

### ✅ Fase 1 completada — Maestros y autenticación

#### Migraciones ejecutadas (orden 2026_05_11_000001..000008)
- `titulares` — nombre unique, alias, activo, softDeletes
- `bancos` — nombre unique, codigo, pais char(2)
- `monedas` — codigo unique, es_fiat, es_cripto, decimales tinyint
- `cuentas` — FK titular/banco/moneda, tipo enum, saldo decimal(20,4), unique(titular_id,alias), softDeletes
- `clientes` — fullText(nombre,alias), saldo_cache_usd decimal(20,4), softDeletes
- `categorias_gasto` — FK titular nullable
- `tipos_operacion` — codigo unique, afecta_cliente/fifo/genera_ganancia
- `users` extendida — titular_id FK, activo, last_login_at, softDeletes

#### Modelos creados (`app/Models/`)
`Titular`, `Banco`, `Moneda`, `Cuenta`, `Cliente`, `CategoriaGasto`, `TipoOperacion`
`User` actualizado con `HasApiTokens`, `HasRoles`, `SoftDeletes`

#### Seeder
`CatalogosBaseSeeder` — roles web guard, 5 monedas, 13 bancos, 8 tipos de operación
`DatabaseSeeder` — crea `admin@casacambio.dev` / `password` con rol `super_admin`

#### Nota guard Spatie
Roles creados con `guard_name = 'web'`. `hasRole()` del User model resuelve contra guards del proveedor `users` (web). Funciona correctamente con tokens Sanctum.

#### Policies (`app/Policies/`)
`TitularPolicy`, `BancoPolicy`, `MonedaPolicy`, `CuentaPolicy`, `ClientePolicy`, `CategoriaGastoPolicy`
— Regla: `before()` devuelve `true` para super_admin; admin = CRUD; operador = read+write clientes; contador/lectura = solo read.
Registradas en `AppServiceProvider::boot()` vía `Gate::policy()`.

#### Form Requests (`app/Http/Requests/`)
`Titular/Store|Update`, `Banco/Store|Update`, `Moneda/Store|Update`,
`Cuenta/Store|Update`, `Cliente/Store|Update`, `CategoriaGasto/Store|Update`, `Auth/Login`

#### Controllers (`app/Http/Controllers/`)
`AuthController` — login, logout, me
`TitularController`, `BancoController`, `MonedaController`, `CuentaController`,
`ClienteController`, `CategoriaGastoController` — apiResource completo

#### Rutas (`routes/api.php`)
Prefijo `/api/v1`. Login público. Todo lo demás bajo `auth:sanctum`.
`CategoriaGasto` usa parámetro de ruta `categoria_gasto` (snake_case).
Registrado en `bootstrap/app.php` vía `api:` key.

#### Verificado con curl
```
POST /api/v1/auth/login → token + user con roles ✓
GET  /api/v1/auth/me    → datos del usuario autenticado ✓
```

### ✅ Fase 2 completada — Ledger contable con partida doble

#### Migraciones ejecutadas
- `operaciones` — todos los campos con ganancia en USD y VES, campos de trazabilidad (origen, origen_referencia UNIQUE), índices en fecha/tipo/estatus/cliente/operador.
- `movimientos` — FK a operacion (cascade delete), cuenta, moneda; monto signed, tasa_a_usd, monto_usd_equivalente materializado, orden.

#### Modelos
- `Operacion` — fillable completo, casts de decimales, SoftDeletes, 6 relaciones.
- `Movimiento` — fillable, casts, 3 relaciones (operacion, cuenta, moneda).
- Jobs stubs: `RecalcularSaldoCuentaJob`, `ProcesarFifoOperacionJob` (lógica en Fases 3/4).

#### RegistroOperacionService
- `registrar(array $payload): Operacion` — transacción atómica.
- `validarMovimientos`: reglas por tipo (ajuste_apertura=1 mov, gasto/comision/ajuste=sin cuadre, venta/compra/cambio/traslado=mín 2 + Σ(monto×tasa)≈0 con TOLERANCIA 0.01 USD). Rechaza cuentas inactivas listando aliases.
- `calcularGananciaDirecta`: retorna `['usd'=>float, 'ves'=>float]` por tipo:
  - `venta_usd`: ganancia_ves = monto_usd_vendido × (tasa_aplicada − tasa_mercado); ganancia_usd = ganancia_ves / tasa_aplicada.
  - `compra_usd`: ganancia_ves = monto_usd_comprado × (tasa_mercado − tasa_aplicada); ganancia_usd = ganancia_ves / tasa_mercado.
  - `comision`: VES directo si moneda=VES, sino convierte con tasa_mercado.
  - `cambio`: 0/0 (TODO Fase 4 con bilateral rate).

#### Fix crítico
`AuthorizesRequests` trait agregado al base `Controller` — Laravel 11 no lo incluye por default.

#### Nota diseño: tasa_a_usd en compra_usd
El movimiento VES usa `tasa_a_usd = 1/tasa_aplicada` (la tasa pactada) para que Σ=0. La ganancia se calcula del diferencial vs tasa_mercado_snapshot en el campo de la operación, no en los movimientos.

#### API
- `GET/POST /api/v1/operaciones` — index paginado (filtros: fecha_desde/hasta, tipo_codigo, cliente_id, operador_id, estatus, cuenta_id), store.
- `GET /api/v1/operaciones/{id}` — show con eager loads completos.
- `PATCH /api/v1/operaciones/{id}/verificar` — solo contador/admin/super_admin.
- `DELETE /api/v1/operaciones/{id}` — retorna 405 con mensaje explicativo.

#### Resources
- `OperacionResource` + `MovimientoResource` (montos como string para precisión decimal).

#### Factories creadas
`Titular`, `Banco`, `Moneda` (con states usd/ves/usdt), `Cuenta`, `Cliente`, `TipoOperacion` (con states por codigo), `Operacion`. `UserFactory` actualizado con `activo=true, titular_id=null`.

#### Tests ✅ 21/21 verde
- **Unit (15)**: cuadre, no-cuadre, tolerancia, cuenta inactiva, gasto 1 mov, ajuste_apertura>1 mov falla, ganancia venta USD/VES, sin snapshot→0, dispatch RecalcularSaldo, dispatch FIFO, unique origen_referencia, moneda de cuenta (no payload), ganancia compra, comision VES, traslado sin ganancia.
- **Feature (6)**: index paginado, store 201, 401 sin auth, show con movimientos, verificar roles, destroy 405.

### Pendiente backend
- [ ] Fase 3: `RecalcularSaldoCuentaJob` implementado + endpoint de saldos actuales
- [ ] Fase 4: `FifoService` + tabla `lotes_fifo` con clave (titular_id, moneda_id) — NO cuenta_id
- [ ] Fase 5: `ReportesService` — P&L periodo = Σganancia_directa - Σgastos
- [ ] Tests unitarios: FifoService, ComisionesService

---

## Estado actual del frontend (`app/`)

### Versiones
- Flutter: 3.x (SDK ^3.11.3)
- Nombre paquete: `casa_cambio_app`
- Plataformas: web, android, ios
- Dev browser: **Edge** (Chrome no instalado en esta máquina)
- Puerto web: `http://localhost:3000`

### Dependencias instaladas
| Paquete | Versión | Rol |
|---|---|---|
| flutter_riverpod | ^2.6.1 | Estado global |
| riverpod_annotation | ^2.6.1 | Anotaciones codegen |
| go_router | ^15.1.2 | Routing declarativo |
| dio | ^5.8.0+1 | HTTP client |
| intl | ^0.20.2 | Fechas y moneda |
| fl_chart | ^0.70.2 | Gráficos |
| freezed_annotation | ^3.0.0 | Modelos inmutables (**debe ser ^3.x por riverpod_generator**) |
| json_annotation | ^4.9.0 | Serialización JSON |
| build_runner | ^2.4.15 | Codegen |
| freezed | ^3.0.0 | Codegen modelos |
| json_serializable | ^6.9.5 | Codegen JSON |
| riverpod_generator | ^2.6.5 | Codegen providers |
| riverpod_lint / custom_lint | ^2.6.5 / ^0.7.5 | Análisis estático |

### Archivos creados
```
lib/
├── main.dart                      ← ProviderScope + MaterialApp.router
├── app_router.dart                ← GoRouter: /login → AuthScreen, /dashboard → DashboardScreen
├── core/
│   ├── constants/api_constants.dart   ← baseUrl, sanctumCsrf
│   ├── theme/app_theme.dart           ← Material3, seed azul #1565C0
│   ├── network/dio_client.dart        ← factory DioClient
│   └── auth/auth_provider.dart        ← authStateProvider (StateProvider<bool>)
├── features/
│   ├── auth/auth_screen.dart
│   ├── dashboard/dashboard_screen.dart
│   ├── operaciones/operaciones_screen.dart
│   ├── cuentas/cuentas_screen.dart
│   ├── clientes/clientes_screen.dart
│   └── reportes/reportes_screen.dart
└── shared/widgets/                    ← vacío, listo para componentes compartidos
```

### Pendiente frontend
- [ ] Pantalla de Login completa (Form, validación, llamada API)
- [ ] AuthNotifier con Riverpod (login/logout/token persist)
- [ ] Redirección en GoRouter basada en estado de auth
- [ ] DioClient con interceptor de token Bearer y manejo de 401
- [ ] Features completas: Operaciones, Cuentas, Clientes, Reportes, Dashboard
- [ ] Modelos Dart (freezed + json_serializable) por recurso

---

## Cómo iniciar el entorno de desarrollo

### MySQL (Laragon)
```powershell
Start-Process "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe" -ArgumentList "--defaults-file=C:\laragon\bin\mysql\mysql-8.4.3-winx64\my.ini"
```

### Backend
```powershell
cd "Intermedius ERP\api"
php artisan serve --port=8000
```

### Frontend
```powershell
cd "Intermedius ERP\app"
flutter run -d edge --web-port=3000
```

---

## Decisiones técnicas tomadas
1. `activitylog` fijado en `4.9.0` — no actualizar hasta migrar a PHP 8.4.
2. `horizon` se instala ignorando `ext-pcntl/posix` (solo desarrollo Windows); en producción Linux funciona normal.
3. CORS `config/cors.php` creado manualmente porque Laravel 11 no lo genera por defecto.
4. `freezed_annotation` debe ser `^3.x` para compatibilidad con `riverpod_generator ^2.6.5`.
5. `flutter run` usa `-d edge` porque Chrome no está instalado en esta máquina.
