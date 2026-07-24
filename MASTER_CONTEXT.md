# MASTER CONTEXT — Intermedius

> Contexto completo del proyecto. Generado el 2026-07-23.
> Stack: Laravel 11 (PHP 8.4) + Vue 3 (Vite/Pinia) + MariaDB + Redis + Docker

---

## 1. QUÉ ES INTERMEDIUS

Plataforma web para una **casa de cambio venezolana**. Reemplaza un sistema administrativo en Excel.
Gestiona clientes, cuentas bancarias, operaciones de compra/venta de divisas (USD/USDT/EUR/COP/VES),
comisiones, tasas de cambio, pool de pagadores, gastos, reportes contables y más.

**Dominios en prod:** `api.intermediusg.com` (backend, puerto 8081), `admin.intermediusg.com` (frontend, puerto 8082)

---

## 2. TECH STACK

| Capa | Tecnología |
|------|-----------|
| Backend | Laravel 11, PHP 8.4 |
| Frontend | Vue 3 (Composition API, `<script setup>`), Vite, Pinia, Vue Router 4, Axios |
| CSS | Tailwind CSS |
| BD | MariaDB 10.3 |
| Auth | Laravel Sanctum + Spatie Permission (6 roles) |
| Auditoría | Spatie ActivityLog |
| Reportes | maatwebsite/excel, barryvdh/laravel-dompdf |
| Colas | Laravel Horizon + Redis |
| Archivos | MinIO (S3-compatible) vía Flysystem |
| WebSockets | Laravel Reverb + Echo (Pusher protocol) |
| Infra | Docker Compose (dev), VPS Hetzner + aaPanel + Nginx (prod) |

---

## 3. ARQUITECTURA DOCKER (LOCAL)

| Servicio | Puerto | Descripción |
|----------|--------|-------------|
| api | 8000 | Laravel `artisan serve` |
| frontend | 3000 | Vue 3 Vite dev |
| db | 3306 | MariaDB 10.3 |
| redis | 6379 | Redis 7 |
| minio | 9000/9001 | S3-compatible |
| mailpit | 8025/1025 | Mail UI/SMTP |
| horizon | — | Laravel Horizon |
| schedule | — | `artisan schedule:work` |
| reverb | 8080 | WebSocket server |

---

## 4. ESTRUCTURA DEL BACKEND (`api/`)

```
api/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php              # login/logout/me/verificar-email
│   │   ├── TitularController.php           # CRUD
│   │   ├── BancoController.php             # CRUD
│   │   ├── MonedaController.php            # CRUD
│   │   ├── CuentaController.php            # CRUD + cargarSaldo
│   │   ├── ClienteController.php           # CRUD + cuentas/operaciones/restaurar/documentos
│   │   ├── CategoriaGastoController.php    # CRUD
│   │   ├── TasasController.php             # actuales/historico
│   │   └── Api/V1/
│   │       ├── OperacionController.php     # CRUD + verificar + solicitud/iniciar/cerrar/cancelar + ganancia-preview
│   │       ├── TransaccionController.php   # CRUD + confirmar/revertir
│   │       ├── PoolController.php          # index/misOrdenes/tomar/soltar/pagar/cancelar
│   │       ├── DashboardController.php     # general/tasas-referencia/resumen
│   │       ├── GastoController.php         # index/store/show
│   │       ├── ReporteComisionesController.php # index/exportar/historico
│   │       ├── UserController.php          # CRUD + toggleActivo
│   │       ├── DocumentoController.php     # index/store/destroy/preview/download
│   │       └── Configuracion/
│   │           ├── TasaDiariaController.php      # vigentes/index/historial/store
│   │           ├── ComisionCuentaController.php  # apiResource
│   │           ├── ComisionOperadorController.php # apiResource
│   │           ├── ComisionMetodoPagoController.php # apiResource
│   │           └── ComisionOperacionController.php # index/update
│   ├── Http/Requests/         # Form Requests con validación
│   ├── Http/Resources/        # OperacionResource, TransaccionResource, MovimientoResource
│   ├── Middleware/             # Authenticate (custom 401), SanitizeLogs
│   ├── Models/                 # 16 modelos Eloquent
│   ├── Services/
│   │   ├── Operaciones/RegistroOperacionService.php   # crear solicitud, iniciar, cerrar, cancelar, validar balance
│   │   ├── Configuracion/TasaDiariaService.php         # publicar, obtenerVigente, validarTasa
│   │   ├── Configuracion/CalculadorComisionesService.php # calcular/aplicar comisiones
│   │   └── TasasMercadoService.php                     # sincronizar BCV/Binance
│   ├── Jobs/                   # SincronizarTasas, AlertarTasasFaltantes, VerificarSlaPool, etc.
│   ├── Events/                 # SlaExcedida (broadcast → Reverb)
│   └── Policies/               # Banco, Cliente, Cuenta, Moneda, Operacion, Titular, CategoriaGasto
├── config/
│   ├── sistema.php             # pares_principales, moneda_referencia (USD), moneda_local (VES)
│   ├── fifo.php                # permitir_sobregiro, politica_costo_sobregiro, tolerancia_consumo
│   ├── reportes.php            # comisiones_operadores config
│   └── permission.php          # Spatie Permission config
├── database/
│   ├── schema/mysql.sql        # Schema completo (creación de tablas)
│   ├── seed.sql                # Datos iniciales
│   └── migrations/             # Solo login_attempts y documentos (stub)
├── routes/
│   ├── api.php                 # Todas las rutas API (prefix /api/v1)
│   └── console.php             # Schedule de jobs
└── docker/                     # Dockerfile, entrypoint, php.ini
```

---

## 5. MODELOS ELOQUENT (16)

| Modelo | Tabla | Relaciones clave |
|--------|-------|------------------|
| User | users | HasRoles, HasApiTokens, MustVerifyEmail, SoftDeletes |
| Titular | titulares | hasMany Cuenta, hasMany User, hasMany CategoriaGasto |
| Cliente | clientes | hasMany Cuenta, hasMany Documento, hasMany Operacion |
| Cuenta | cuentas | morphTo (titular/cliente), belongsTo Banco, belongsTo Moneda |
| Moneda | monedas | hasMany Cuenta, hasMany Movimiento, hasMany Comision* |
| Banco | bancos | hasMany Cuenta, hasMany ComisionCuenta |
| Operacion | operaciones | belongsTo TipoOperacion, Moneda, Cliente, User (operador/verificador/pagador); hasMany Movimiento, Transaccion, ComisionOperacion |
| Movimiento | movimientos | belongsTo Operacion, Cuenta, Moneda |
| Transaccion | transacciones | belongsTo Operacion, Cuenta (origen/destino), Moneda, User (confirmador) |
| TipoOperacion | tipos_operacion | hasMany Operacion |
| TasaDiaria | tasas_diarias | belongsTo Moneda (base/cotizada), User |
| TasaMercado | tasas_mercado | belongsTo Moneda (base/cotizada) |
| ComisionOperacion | comisiones_operacion | morphTo origen, belongsTo Operacion, Movimiento |
| ComisionCuenta | comisiones_cuenta | belongsTo Cuenta, Moneda |
| ComisionOperador | comisiones_operador | belongsTo Titular, TipoOperacion, Moneda |
| ComisionMetodoPago | comisiones_metodo_pago | belongsTo Moneda |
| Documento | documentos | belongsTo Cliente |
| CategoriaGasto | categorias_gasto | belongsTo Titular |
| LoginAttempt | login_attempts | — |

---

## 6. ESTADOS DE OPERACIÓN

### Flujo Multi-Paso (actual, feat/flujo-multi-paso)
```
solicitud ──[iniciar]──→ en_progreso ──[cerrar]──→ cerrada
     │                      │
     └──[cancelar]──────────┴──[cancelar]──→ cancelada
```

### Flujo Legacy (estatus)
```
sin_verificar ──[iniciar-verificacion]──→ en_verificacion ──[verificar]──→ verificado
```

### Pool de Pagadores (estado_pool)
```
pendiente ──[tomar]──→ asignada ──[pagar]──→ pagada
                              │
                              └──[soltar]──→ pendiente
```

---

## 7. API ENDPOINTS (prefix `/api/v1`)

### Públicas
| Método | URI | Middleware |
|--------|-----|------------|
| POST | `/auth/login` | throttle:5,1 |
| POST | `/auth/verificar-email` | throttle:10,1 |
| GET | `/email/verify/{id}/{hash}` | signed |
| GET | `/documentos/{id}/preview` | token en query |
| GET | `/documentos/{id}/download` | token en query |

### Autenticación
| Método | URI | Middleware |
|--------|-----|------------|
| POST | `/auth/logout` | auth:sanctum |
| GET | `/auth/me` | auth:sanctum |

### Catálogos (apiResource)
| Recurso | Controller |
|---------|------------|
| `/titulares` | TitularController |
| `/bancos` | BancoController |
| `/monedas` | MonedaController |
| `/cuentas` | CuentaController |
| `/clientes` | ClienteController |
| `/categorias-gasto` | CategoriaGastoController |

### Operaciones (Multi-Paso + Legacy)
| Método | URI | Middleware |
|--------|-----|------------|
| GET/POST | `/operaciones` | auth:sanctum |
| GET/PUT | `/operaciones/{operacion}` | auth:sanctum |
| PATCH | `/operaciones/{operacion}/verificar` | auth:sanctum |
| DELETE | `/operaciones/{operacion}` | auth:sanctum (siempre 405) |
| POST | `/operaciones/solicitud` | auth:sanctum |
| POST | `/operaciones/{operacion}/iniciar` | auth:sanctum |
| POST | `/operaciones/{operacion}/cerrar` | auth:sanctum |
| POST | `/operaciones/{operacion}/cancelar` | auth:sanctum |
| GET | `/operaciones/{operacion}/ganancia-preview` | auth:sanctum |

### Transacciones (Multi-Paso)
| Método | URI | Middleware |
|--------|-----|------------|
| POST | `/operaciones/{operacion}/transacciones` | auth:sanctum |
| PUT | `/operaciones/{operacion}/transacciones/{transaccion}` | auth:sanctum |
| DELETE | `/operaciones/{operacion}/transacciones/{transaccion}` | auth:sanctum |
| PATCH | `/operaciones/{operacion}/transacciones/{transaccion}/confirmar` | auth:sanctum |
| PATCH | `/operaciones/{operacion}/transacciones/{transaccion}/revertir` | auth:sanctum |

### Pool
| Método | URI | Middleware |
|--------|-----|------------|
| GET | `/pool` | auth:sanctum + role:pagador\|admin\|super_admin |
| GET | `/pool/mis-ordenes` | auth:sanctum + role:pagador\|admin\|super_admin |
| POST | `/pool/{operacion}/tomar` | auth:sanctum + role:pagador\|admin\|super_admin |
| POST | `/pool/{operacion}/soltar` | auth:sanctum + role:pagador\|admin\|super_admin |
| POST | `/pool/{operacion}/pagar` | auth:sanctum + role:pagador\|admin\|super_admin |
| POST | `/pool/{operacion}/cancelar` | auth:sanctum + role:admin\|super_admin |

### Comisiones por Operación
| Método | URI | Middleware |
|--------|-----|------------|
| GET | `/operaciones/{operacion}/comisiones` | auth:sanctum + role:admin\|super_admin\|contador |
| PATCH | `/operaciones/{operacion}/comisiones/{comision}` | auth:sanctum + role:admin\|super_admin |

### Tasas
| Método | URI |
|--------|-----|
| GET | `/tasas/actuales` |
| GET | `/tasas/historico` |

### Dashboard
| Método | URI |
|--------|-----|
| GET | `/dashboard/general` |
| GET | `/dashboard/tasas-referencia` |
| GET | `/dashboard/resumen` |

### Gastos
| Método | URI |
|--------|-----|
| GET | `/gastos` |
| POST | `/gastos` |
| GET | `/gastos/{operacion}` |

### Configuración
| Método | URI | Middleware adicional |
|--------|-----|---------------------|
| GET | `/configuracion/tasas-vigentes` | — |
| GET/POST | `/configuracion/tasas-diarias` | POST: role:admin\|super_admin |
| GET | `/configuracion/tasas-diarias/historial/{base}/{cotizada}` | — |
| CRUD | `/configuracion/comisiones-cuenta` | role:admin\|super_admin |
| CRUD | `/configuracion/comisiones-operador` | role:admin\|super_admin |
| CRUD | `/configuracion/comisiones-metodo-pago` | role:admin\|super_admin |

### Reportes
| Método | URI | Middleware |
|--------|-----|------------|
| GET | `/reportes/comisiones-operadores` | role:admin\|super_admin\|contador |
| POST | `/reportes/comisiones-operadores/exportar` | role:admin\|super_admin\|contador |
| GET | `/reportes/comisiones-operadores/historico` | role:admin\|super_admin\|contador |

### Usuarios
| Método | URI | Middleware |
|--------|-----|------------|
| CRUD | `/usuarios` | role:admin\|super_admin |
| PATCH | `/usuarios/{user}/toggle-activo` | role:admin\|super_admin |

### Documentos
| Método | URI |
|--------|-----|
| GET/POST | `/clientes/{cliente}/documentos` |
| DELETE | `/documentos/{documento}` |
| GET | `/documentos/{documento}/preview` |
| GET | `/documentos/{documento}/download` |

### Clientes — Extras
| Método | URI |
|--------|-----|
| GET | `/clientes/{cliente}/cuentas` |
| GET | `/clientes/{cliente}/operaciones` |
| POST | `/clientes/{cliente}/operaciones/exportar` |
| POST | `/clientes/{cliente}/restaurar` |

### Cuentas — Extras
| Método | URI |
|--------|-----|
| POST | `/cuentas/{cuenta}/saldo` |
| GET | `/cuentas/{cuenta}/saldo-disponible` |

### Bitácora
| Método | URI | Middleware |
|--------|-----|------------|
| GET | `/admin/bitacora` | role:super_admin |

---

## 8. CÁLCULO DE GANANCIA

| Tipo | Fórmula |
|------|---------|
| `venta_usd` | `ganancia_ves = monto_divisa × (tasa_aplicada − tasa_mercado)` |
| | `ganancia_usd = ganancia_ves / tasa_aplicada` |
| `compra_usd` | `ganancia_ves = monto_divisa × (tasa_mercado − tasa_aplicada)` |
| | `ganancia_usd = ganancia_ves / tasa_mercado` |
| `intermediada` | `ganancia_ves = montoDivisa × (tasa_venta − tasa_compra)` |
| | `ganancia_usd = ganancia_ves / tasa_venta` |
| `comision` | directa desde `monto_usd_equivalente` |
| Netas | `bruta − comisiones` (CalculadorComisionesService) |

- `genera_ganancia = true` para: `venta_usd`, `compra_usd`, `intermediada`, `comision`
- `tasa_mercado_snapshot` se actualiza al **cerrar** la operación (no al crear solicitud)
- Preview: `GET /operaciones/{id}/ganancia-preview?tasa_mercado=X`

---

## 9. ROLES DEL SISTEMA

| Rol | Acceso |
|-----|--------|
| `super_admin` | Total (bypasea policies vía `before()`) |
| `admin` | CRUD completo, verificar, pool, config |
| `operador` | Crear ops, ver catálogos; create condicionado en Cuenta (solo titular "Terceros") |
| `contador` | Lectura + reportes + verificar |
| `lectura` | Solo lectura (viewAny/view) |
| `pagador` | Pool (tomar/soltar/pagar, no cancelar) |

---

## 10. ESTRUCTURA DEL FRONTEND (`frontend/`)

```
frontend/src/
├── main.js                    # Entry: createApp, Pinia, Router
├── App.vue                    # <router-view />
├── index.css                  # Tailwind directives
├── errorHandler.js            # Global error handler
├── api/axios.js               # Axios instance + interceptors (Bearer token, 401 redirect)
├── plugins/echo.js            # Laravel Echo + Reverb
├── stores/                    # 9 Pinia stores
│   ├── auth.js                # Token, user, roles, login/logout
│   ├── operaciones.js         # CRUD + solicitar/iniciar/cerrar/cancelar/ganancia-preview
│   ├── pool.js                # Pool: fetch/tomar/soltar/pagar/cancelar
│   ├── clientes.js            # CRUD + soft-delete/restore
│   ├── tasas.js               # Vigentes/historial/publicar
│   ├── tasasReferencia.js     # Tasas referencia dashboard
│   ├── bancos.js              # CRUD
│   ├── titulares.js           # CRUD
│   └── usuarios.js            # CRUD + toggleActivo
├── composables/               # 10 composables
│   ├── useApi.js              # Core: AbortController, loading/error/data
│   ├── useAuth.js             # Login/logout/fetchMe
│   ├── useOperaciones.js      # CRUD + solicitar/iniciar/cerrar/cancelar
│   ├── useTransacciones.js    # CRUD transacciones + confirmar/revertir
│   ├── useOperacionForm.js    # Lógica del formulario de solicitud
│   ├── usePool.js             # Pool con computed filters
│   ├── useClientes.js         # CRUD + search + restore
│   ├── useCuentas.js          # Fetch + filtros
│   ├── useTasas.js            # Vigentes/historial/getTasaPar
│   ├── useBancos.js           # CRUD
│   ├── useTitulares.js        # Fetch + getIntermedius
│   ├── useNotification.js     # Toast notifications
│   └── useInactivityTimer.js  # Auto-logout 30min
├── components/                # 20 componentes
│   ├── common/                # AppPageHeader, AppEmptyState, AppLoadingSpinner, AppErrorState
│   ├── layout/AppShell.vue    # Sidebar + navbar + router-view
│   ├── clientes/ClienteSelector.vue
│   ├── cuentas/CuentaSelector.vue
│   ├── pool/                  # PoolTimer, PoolList, PoolAlarm, PoolActions
│   ├── operaciones/           # CalculadoraBidireccional, ResumenOperacion, TransaccionRow
│   │   └── form/              # OperacionFormCabecera, Transacciones, Comision, Resumen
│   └── configuracion/         # TipoOperacionSelector, ComisionToggle
├── views/                     # 19 vistas
│   ├── auth/                  # LoginView, EmailVerifyView
│   ├── operaciones/           # OperacionesView, OperacionFormView, OperacionIntermediadaForm,
│   │                          # OperacionDetailView, OperacionMonedaView, GestionarOperacionView
│   ├── catalogos/             # ClientesView, CuentasView, TitularesView, BancosView, UsuariosView
│   ├── configuracion/         # TasasView, ComisionesView
│   ├── dashboard/DashboardView.vue
│   ├── pool/PoolView.vue
│   ├── reportes/ReportesView.vue
│   └── NotFoundView.vue
└── router/index.js            # 19 rutas + beforeEach guard
```

---

## 11. REGLAS DE NEGOCIO CLAVE

1. **Partida doble:** Σ movimientos convertidos a USD ≈ 0 (tolerancia 0.01)
2. **Tasas del día:** Admin publica tasas diarias. Operador puede usar tasa distinta solo si es favorable a la casa.
3. **Comisiones:** Se calculan automáticamente al registrar/cerrar. Snapshot inmodificable salvo por admin con razón.
4. **Ganancia:** `bruta = diferencia tasas × monto`. `neta = bruta − comisiones`.
5. **FIFO:** No implementado (stub). Pendiente Fase 4-C.
6. **BCV/Binance son referenciales:** No se usan para ganancia, solo dashboard.
7. **Balance al cerrar:** suma divisa = `monto_solicitado`, suma VES = `monto_solicitado × tasa` (tolerancia 0.01)
8. **Comprobante:** Obligatorio si `metodo_pago ≠ efectivo`.
9. **Direccionalidad:** Compra = casa compra divisa (cliente entrega divisa → casa entrega VES). Venta = casa vende divisa.
10. **Cuenta dueño:** XOR (titular o cliente), nunca ambos.
11. **esDivisa:** cualquier moneda ≠ VES funciona como divisa.
12. **Límite monto transacciones:** Excluye `revertida`/`cancelada`.

---

## 12. JOBS PROGRAMADOS

| Job | Frecuencia | Descripción |
|-----|------------|-------------|
| `SincronizarTasasJob` | Cada minuto | Captura tasas BCV (dolarapi.com) y Binance P2P |
| `SincronizarTasasReferenciaJob` | Cada minuto | Mismo que el anterior (duplicado) |
| `VerificarSlaPoolJob` | Cada minuto | Emite `SlaExcedida` si operación pool > 5 min |
| `AlertarTasasFaltantesJob` | 08:00 y 14:00 | Alerta si falta tasa del día |
| `GenerarReporteMensualComisionesJob` | Día 1, 06:00 | Genera Excel+PDF comisiones mes anterior |
| `AutoArchivarClientesInactivos` | Domingo 03:00 | Desactiva clientes sin ops en 4 meses |

---

## 13. EVENTOS (WebSocket)

| Evento | Canal | Propósito |
|--------|-------|-----------|
| `sla.excedida` | `pool` (público) | Alerta cuando operación pool supera 5 min SLA |

Flujo: `VerificarSlaPoolJob` → `SlaExcedida` event → `BroadcastEvent` queue → Reverb → Echo → `PoolAlarm.vue`

---

## 14. CONFIGURACIONES DEL SISTEMA

| Archivo | Variable clave | Valor |
|---------|---------------|-------|
| `config/sistema.php` | `moneda_referencia` | `USD` |
| | `moneda_local` | `VES` |
| | `pares_principales` | `['USD/VES', 'USDT/VES']` |
| `config/fifo.php` | `permitir_sobregiro` | `true` |
| | `politica_costo_sobregiro` | `'tasa_movimiento'` |
| | `tolerancia_consumo` | `0.0001` |

---

## 15. HALLAZGOS CRÍTICOS (AUDITORÍA JUL 2026)

### Seguridad (ver INFORME_AUDITORIA_INTERMEDIUS.md)
- 🔴 Credenciales de producción en repositorio (DB_PASSWORD, APP_KEY)
- 🔴 Sin rate limiting en login (fuerza bruta)
- 🔴 Tokens Sanctum sin expiración
- 🔴 Sin 2FA
- 🔴 Datos sensibles sin cifrar en DB
- 🔴 Sin AML/KYC (riesgo regulatorio existencial)
- 🔴 Sin backups documentados
- 🟠 Token en localStorage (vulnerable a XSS)
- 🟠 Sin CSP/HSTS
- 🟠 Authorización faltante en `verificar()`

### Rendimiento (ver AGENTS.md)
- 🔴 Xdebug activo en Docker
- 🔴 Opcache deshabilitado
- 🔴 Cache/Queue en DB en vez de Redis
- 🟡 N+1 en `RegistroOperacionService` (Cuenta::find en loops)
- 🟡 Dashboard sin paginación

### Deuda Técnica
- `OperacionController` ~650 líneas (debe refactorizarse)
- FIFO no implementado (stub)
- Frontend sin TypeScript, sin tests
- Jobs de tasas duplicados
- Migraciones de tablas core no existen (solo SQL directo)
- Exception handler vacío

---

## 16. COMANDOS ÚTILES

```bash
# Docker
docker compose up --build -d
docker compose exec api composer update
docker compose exec api php artisan tinker
docker compose logs api --tail=20

# Seeders
docker compose exec api php artisan db:seed

# Tests (216 existentes)
docker compose exec api php artisan test
```
