# Controllers — AI Context

## Base Controller

### `App\Http\Controllers\Controller`
- Clase abstracta base para todos los controladores.
- Usa el trait `AuthorizesRequests` para autorización vía Policies.
- Sin rutas directas.

---

## Web Controllers (raíz)

### `App\Http\Controllers\AuthController`
- **Rutas**: `/api/v1/auth/login` (pública), `/api/v1/auth/logout`, `/api/v1/auth/me`, `/api/v1/auth/verificar-email` (pública), `/api/v1/email/verify/{id}/{hash}` (firmada)

| Método | Endpoint | Propósito | Request | Middleware | Policy |
|---|---|---|---|---|---|
| login | POST /auth/login | Autentica usuario con email/password. Verifica que el usuario esté activo y tenga `email_verified_at`. Actualiza `last_login_at`. Crea y devuelve token Sanctum con datos del usuario y roles. | `LoginRequest` | público (ninguno) | — |
| logout | POST /auth/logout | Revoca el token de acceso actual del usuario autenticado. | `Request` (vacío) | `auth:sanctum` | — |
| me | GET /auth/me | Devuelve los datos del usuario autenticado (id, name, email, roles, titular_id, last_login_at). | `Request` (vacío) | `auth:sanctum` | — |
| verificarEmail | POST /auth/verificar-email | Verifica email de usuario (flujo SPA). Recibe `email` + `hash` (SHA1 del email), valida con `hash_equals()`, marca `email_verified_at`. Rate limit: 10/min. | `Request` (validación inline: email, hash) | público, throttle:10,1 | — |
| verifyEmail | GET /email/verify/{id}/{hash} | Verifica email (endpoint legacy con URL firmada). Recibe `id` + `hash` de ruta, valida y marca verificado. | `Request` (parámetros ruta) | `signed` | — |

**Lógica importante**:
- Si el usuario no está `activo` en login, retorna 403.
- Si `email_verified_at` es null en login, retorna 403 con mensaje "Debe verificar su correo".
- `verificarEmail` usa `hash_equals()` para comparación segura del hash.
- `verifyEmail` es endpoint legacy mantenido para compatibilidad.

---

### `App\Http\Controllers\ClienteController`
- **Rutas**: `/api/v1/clientes` (apiResource)
- **Middleware**: todas bajo `auth:sanctum`

| Método | Endpoint | Propósito | Request | Policy (ability) |
|---|---|---|---|---|
| index | GET /clientes | Listar clientes paginados (50). Filtros: `?q=` búsqueda por nombre/alias, `?inactivos=true` para incluir soft-deleteados. | `Request` | `viewAny` |
| store | POST /clientes | Crear un nuevo cliente. | `StoreClienteRequest` | `create` |
| show | GET /clientes/{cliente} | Ver detalle del cliente con `cuentas.banco` y `cuentas.moneda`. | — (route model binding) | `view` |
| update | PUT /clientes/{cliente} | Actualizar datos del cliente. | `UpdateClienteRequest` | — (no policy explícita) |
| destroy | DELETE /clientes/{cliente} | Soft-delete del cliente. | — | `delete` |
| cuentas | GET /clientes/{cliente}/cuentas | Listar cuentas del cliente ordenadas por alias con banco y moneda. | — | `view` |
| operaciones | GET /clientes/{cliente}/operaciones | Operaciones del cliente paginadas (20). Filtros: `fecha_desde`, `fecha_hasta`, `tipo_codigo`. | `Request` | `view` |
| exportarOperaciones | POST /clientes/{cliente}/operaciones/exportar | Exporta operaciones a PDF usando DomPDF (vista `reportes.cliente_operaciones`). Mismos filtros que operaciones. | `Request` | `view` |
| restaurar | POST /clientes/{cliente}/restaurar | Restaura un cliente soft-deleteado. Valida que esté eliminado (422 si no). | — | `restore` |

**Middleware extra**: `restaurar` → `role:admin|super_admin`
**Lógica importante**: `exportarOperaciones` retorna un `Response` con `Content-Type: application/pdf`.

---

### `App\Http\Controllers\CuentaController`
- **Rutas**: `/api/v1/cuentas` (apiResource) + `POST /cuentas/{cuenta}/saldo`
- **Middleware**: todas bajo `auth:sanctum`

| Método | Endpoint | Propósito | Request | Policy | Roles |
|---|---|---|---|---|---|
| index | GET /cuentas | Listar cuentas con titular, cliente, banco, moneda. Filtros: `titular_id`, `cliente_id`, `moneda_id`. | `Request` | `viewAny` | — |
| store | POST /cuentas | Crear cuenta bancaria. Retorna con relaciones cargadas. | `StoreCuentaRequest` | — | — |
| show | GET /cuentas/{cuenta} | Ver detalle de cuenta con relaciones. | — | `view` | — |
| update | PUT /cuentas/{cuenta} | Actualizar cuenta. Retorna fresh con relaciones. | `UpdateCuentaRequest` | — | — |
| destroy | DELETE /cuentas/{cuenta} | Eliminar cuenta (hard-delete). | — | `delete` | — |
| cargarSaldo | POST /cuentas/{cuenta}/saldo | Actualiza `saldo_cache` y `saldo_cache_at`. Validación inline: `saldo` required, numeric, min:0. | `Request` (validación inline) | `update` | `role:admin|super_admin` |

---

### `App\Http\Controllers\BancoController`
- **Rutas**: `/api/v1/bancos` (apiResource)
- **Middleware**: todas bajo `auth:sanctum`

| Método | Endpoint | Propósito | Request | Policy |
|---|---|---|---|---|
| index | GET /bancos | Listar todos los bancos ordenados por nombre. | — | `viewAny` |
| store | POST /bancos | Crear un banco. | `StoreBancoRequest` | — |
| show | GET /bancos/{banco} | Ver detalle de un banco. | — | `view` |
| update | PUT /bancos/{banco} | Actualizar banco. | `UpdateBancoRequest` | — |
| destroy | DELETE /bancos/{banco} | Eliminar banco (hard-delete). | — | `delete` |

---

### `App\Http\Controllers\CategoriaGastoController`
- **Rutas**: `/api/v1/categorias-gasto` (apiResource, parámetro `categoria_gasto`)
- **Middleware**: todas bajo `auth:sanctum`

| Método | Endpoint | Propósito | Request | Policy |
|---|---|---|---|---|
| index | GET /categorias-gasto | Listar categorías con su titular, ordenadas por nombre. | — | `viewAny` |
| store | POST /categorias-gasto | Crear categoría de gasto. | `StoreCategoriaGastoRequest` | — |
| show | GET /categorias-gasto/{categoria_gasto} | Ver detalle con titular. | — | `view` |
| update | PUT /categorias-gasto/{categoria_gasto} | Actualizar categoría. | `UpdateCategoriaGastoRequest` | — |
| destroy | DELETE /categorias-gasto/{categoria_gasto} | Eliminar categoría. | — | `delete` |

---

### `App\Http\Controllers\MonedaController`
- **Rutas**: `/api/v1/monedas` (apiResource)
- **Middleware**: todas bajo `auth:sanctum`

| Método | Endpoint | Propósito | Request | Policy |
|---|---|---|---|---|
| index | GET /monedas | Listar todas las monedas ordenadas por código. | — | `viewAny` |
| store | POST /monedas | Crear moneda. | `StoreMonedaRequest` | — |
| show | GET /monedas/{moneda} | Ver detalle de moneda. | — | `view` |
| update | PUT /monedas/{moneda} | Actualizar moneda. | `UpdateMonedaRequest` | — |
| destroy | DELETE /monedas/{moneda} | Eliminar moneda (hard-delete). | — | `delete` |

---

### `App\Http\Controllers\TitularController`
- **Rutas**: `/api/v1/titulares` (apiResource)
- **Middleware**: todas bajo `auth:sanctum`

| Método | Endpoint | Propósito | Request | Policy |
|---|---|---|---|---|
| index | GET /titulares | Listar titulares. Por defecto solo activos. Filtro `?inactivos=true` para mostrar inactivos. | `Request` | `viewAny` |
| store | POST /titulares | Crear titular. | `StoreTitularRequest` | — |
| show | GET /titulares/{titular} | Ver detalle con cuentas relacionadas. | — | `view` |
| update | PUT /titulares/{titular} | Actualizar titular. | `UpdateTitularRequest` | — |
| destroy | DELETE /titulares/{titular} | Eliminar titular (hard-delete). | — | `delete` |

---

### `App\Http\Controllers\TasasController`
- **Rutas**: `/api/v1/tasas/actuales`, `/api/v1/tasas/historico`
- **Middleware**: todas bajo `auth:sanctum`

| Método | Endpoint | Propósito | Request | Policy |
|---|---|---|---|---|
| actuales | GET /tasas/actuales | Retorna las últimas tasas de cada fuente (bcv, paralelo, binance_p2p_buy, binance_p2p_sell) desde caché o BD, más los spreads calculados (sell vs bcv, buy vs bcv, sell vs buy). | — | — |
| historico | GET /tasas/historico | Histórico paginado (50) de tasas de mercado. Filtros: `fuente`, `desde`, `hasta`. Transforma cada registro a formato par + valor + capturado_en. | `Request` (validación inline) | — |

**Lógica importante**: `actuales` primero busca en Cache (`tasa_actual:{fuente}`). Si no hay, cae a BD. Para Binance incluye extras (mediana, min, max, muestras) del payload_original. Calcula spreads porcentuales. No usa Policies — datos de mercado públicos para autenticados.

---

## API V1 Controllers (Api\V1\)

### `App\Http\Controllers\Api\V1\OperacionController`
- **Rutas**: `/api/v1/operaciones` (apiResource) + `PATCH /operaciones/{operacion}/verificar` + `DELETE /operaciones/{operacion}` (destructor propio)
- **Middleware**: todas bajo `auth:sanctum`
- **Inyección**: `RegistroOperacionService`

| Método | Endpoint | Propósito | Request | Policy | Roles |
|---|---|---|---|---|
| index | GET /operaciones | Lista paginada de operaciones (max 100). Filtros: `fecha_desde`, `fecha_hasta`, `tipo_codigo`, `cliente_id`, `operador_id`, `estatus`, `cuenta_id`. Incluye `tipoOperacion`, `cliente`, `operador`, `movimientos.moneda`. Retorna `OperacionResource` collection. | `Request` | `viewAny` | — |
| store | POST /operaciones | Registra nueva operación vía `RegistroOperacionService::registrar()`. Retorna 201. | `StoreOperacionRequest` | — | — |
| show | GET /operaciones/{operacion} | Detalle completo de operación con todas las relaciones: movimientos.cuenta.titular, movimientos.moneda, tipoOperacion, cliente, clienteEmisor, clienteReceptor, categoriaGasto, operador, verificadoPor, pagador. Incluye logs de depuración. | — | `view` | — |
| update | PUT /operaciones/{operacion} | Actualiza operación vía `RegistroOperacionService::actualizar()`. | `UpdateOperacionRequest` | — | — |
| verificar | PATCH /operaciones/{operacion}/verificar | Cambia estatus a `verificado`, setea `verificado_at` y `verificado_por_id`. Si ya está verificado retorna 422. | `VerificarOperacionRequest` | — | — |
| destroy | DELETE /operaciones/{operacion} | Bloqueado: retorna 405 con mensaje "Las operaciones no se eliminan. Use ajuste manual para corregir." | — | — | — |

---

### `App\Http\Controllers\Api\V1\PoolController`
- **Rutas**: `/api/v1/pool` (grupo)
- **Middleware**: todas bajo `auth:sanctum` + roles específicos

| Método | Endpoint | Propósito | Request | Response | Roles |
|---|---|---|---|---|---|
| index | GET /pool | Lista órdenes pendientes (scope `pendientes()`), más antiguas primero. Paginación. | `Request` | `OperacionResource` collection | `pagador\|admin\|super_admin` |
| misOrdenes | GET /pool/mis-ordenes | Órdenes asignadas al pagador autenticado (scope `asignadasA(userId)`). Ordenadas por `asignada_at` descendente. | `Request` | `OperacionResource` collection | `pagador\|admin\|super_admin` |
| tomar | POST /pool/{operacion}/tomar | Asigna orden al pagador: setea `estado_pool=asignada`, `pagador_id`, `asignada_at`. Si ya no está pendiente → 422. | `Request` | `OperacionResource` | `pagador\|admin\|super_admin` |
| soltar | POST /pool/{operacion}/soltar | Libera orden: `estado_pool=pendiente`, null en pagador_id/asignada_at. Verifica que sea asignada al usuario o admin. | `Request` | `OperacionResource` | `pagador\|admin\|super_admin` |
| marcarPagada | POST /pool/{operacion}/pagar | Marca orden como pagada: `estado_pool=pagada`, `pagada_at`. Verifica que sea asignada al usuario o admin. | `Request` | `OperacionResource` | `pagador\|admin\|super_admin` |
| cancelar | POST /pool/{operacion}/cancelar | Cancela orden (admin/super_admin). Requiere `motivo_cancelacion` (string, max 1000). Si ya está cancelada → 422. | `Request` (validación inline) | `OperacionResource` | `admin\|super_admin` |

**Lógica importante**: El método privado `puedeGestionar` permite operar si el usuario es `admin|super_admin` o si es el `pagador_id` de la orden. Las órdenes cargan eager todas las relaciones definidas en la constante `EAGER`.

---

### `App\Http\Controllers\Api\V1\DashboardController`
- **Rutas**: `/api/v1/dashboard/general`, `/api/v1/dashboard/tasas-referencia`, `/api/v1/dashboard/resumen`
- **Middleware**: todas bajo `auth:sanctum`

| Método | Endpoint | Propósito | Request | Response |
|---|---|---|---|---|
| general | GET /dashboard/general | Tasas vigentes (TasaDiaria activas con monedaBase, monedaCotizada, definidaPor). Referencia mercado (BCV/Binance desde cache o BD). Spreads. Alertas: pares sin tasa vigente, operaciones hoy sin tasa referencia. | — | `JsonResponse` |
| tasasReferencia | GET /dashboard/tasas-referencia | Última tasa capturada de `bcv` y `binance_p2p`. | — | `JsonResponse` |
| resumen | GET /dashboard/resumen | Resumen agregado del período. Filtros: `fecha_desde`, `fecha_hasta`, `moneda`, `operador_id`. Calcula: total/compras/ventas/intermediadas, ganancia bruta/neta, volúmenes por moneda, por operador, efectivo pendiente (heurística sobre descripción "pendiente"). | `Request` (validación inline) | `JsonResponse` |

---

### `App\Http\Controllers\Api\V1\GastoController`
- **Rutas**: `/api/v1/gastos` (GET index, POST store, GET show)
- **Middleware**: todas bajo `auth:sanctum`
- **Inyección**: `RegistroOperacionService`

| Método | Endpoint | Propósito | Request | Policy |
|---|---|---|---|---|
| index | GET /gastos | Lista paginada de operaciones con tipo código `gasto`. Filtros: `fecha_desde`, `fecha_hasta`, `categoria_gasto_id`, `operador_id`. Retorna `OperacionResource` collection. | `Request` | `viewAny` (Operacion) |
| store | POST /gastos | Registra nuevo gasto vía `RegistroOperacionService::registrar()`. Retorna 201. | `StoreGastoRequest` | — |
| show | GET /gastos/{operacion} | Ver detalle de gasto con tipoOperacion, categoriaGasto, operador, movimientos.cuenta.titular, movimientos.moneda. | — | `view` (Operacion) |

---

### `App\Http\Controllers\Api\V1\ReporteComisionesController`
- **Rutas**: `/api/v1/reportes/comisiones-operadores` (grupo)
- **Middleware**: `auth:sanctum` + `role:admin|super_admin|contador`
- **Inyección**: `ReporteComisionesOperadoresService`

| Método | Endpoint | Propósito | Request | Response |
|---|---|---|---|---|
| index | GET /reportes/comisiones-operadores | Genera reporte de comisiones para rango `desde`-`hasta`. Retorna datos del reporte. | `Request` (validación inline) | `JsonResponse` |
| exportar | POST /reportes/comisiones-operadores/exportar | Exporta reporte en Excel o PDF según `formato`. Retorna URL y path del archivo generado. | `Request` (validación inline) | `JsonResponse` |
| historico | GET /reportes/comisiones-operadores/historico | Lista archivos exportados con metadatos (path, url, nombre, tamaño, modificado_en). | — | `JsonResponse` |

---

### `App\Http\Controllers\Api\V1\UserController`
- **Rutas**: `/api/v1/usuarios` (apiResource)
- **Middleware**: `auth:sanctum` + `role:admin|super_admin`

| Método | Endpoint | Propósito | Request | Response |
|---|---|---|---|---|
| index | GET /usuarios | Lista usuarios activos con titular, ordenados por name. | — | `JsonResponse` |
| store | POST /usuarios | Crea usuario con rol. Validación inline: name, email (unique), password (min:8), rol (in: super_admin,admin,operador,contador,lectura), titular_id (nullable), activo (boolean). Asigna rol vía `assignRole()`. | `Request` (validación inline) | `JsonResponse` (201) |
| update | PUT /usuarios/{usuario} | Actualiza usuario. Validación inline con `sometimes`. Sincroniza rol si se envía. Si password presente, hashea. | `Request` (validación inline) | `JsonResponse` |
| destroy | DELETE /usuarios/{usuario} | Desactiva usuario (borrado lógico): setea `activo=false`. No elimina registro. | — | `JsonResponse` |

---

## Configuración Controllers (Api\V1\Configuracion\)

### `App\Http\Controllers\Api\V1\Configuracion\TasaDiariaController`
- **Rutas**: `/api/v1/configuracion/tasas-vigentes` (GET), `/api/v1/configuracion/tasas-diarias` (GET, POST), `/api/v1/configuracion/tasas-diarias/historial/{base}/{cotizada}` (GET)
- **Middleware**: `auth:sanctum`. POST tasas-diarias requiere `role:admin|super_admin`.
- **Inyección**: `TasaDiariaService`

| Método | Endpoint | Propósito | Request | Roles |
|---|---|---|---|---|
| index | GET /configuracion/tasas-diarias | Lista tasas diarias para una fecha (default hoy). Filtros opcionales: `moneda_base_id`, `moneda_cotizada_id`. | `Request` | — |
| store | POST /configuracion/tasas-diarias | Publica nueva tasa diaria vía `TasaDiariaService::publicar()`. Retorna 201. | `StoreTasaDiariaRequest` | `admin\|super_admin` |
| vigentes | GET /configuracion/tasas-vigentes | Tasas vigentes (sin `vigente_hasta`). Mapea a formato con par, tasas, definida_a_las, moneda_base_id, moneda_cotizada_id. | — | — |
| historial | GET /configuracion/tasas-diarias/historial/{base}/{cotizada} | Historial paginado (50) para un par de monedas (IDs como parámetros de ruta). | — (parámetros ruta: `base`, `cotizada`) | — |

---

### `App\Http\Controllers\Api\V1\Configuracion\ComisionCuentaController`
- **Rutas**: `/api/v1/configuracion/comisiones-cuenta` (apiResource, parámetro `comisionCuenta`)
- **Middleware**: `auth:sanctum` + `role:admin|super_admin`

| Método | Endpoint | Propósito | Request | Response |
|---|---|---|---|---|
| index | GET /comisiones-cuenta | Lista paginada (50) con cuenta, banco, moneda. Filtro opcional: `activa`. | `Request` | `JsonResponse { data }` |
| store | POST /comisiones-cuenta | Crear comisión por cuenta. Retorna 201 con relaciones. | `StoreComisionCuentaRequest` | `JsonResponse { data }` (201) |
| show | GET /comisiones-cuenta/{comisionCuenta} | Detalle con relaciones. | — | `JsonResponse { data }` |
| update | PUT /comisiones-cuenta/{comisionCuenta} | Actualizar comisión. | `StoreComisionCuentaRequest` | `JsonResponse { data }` |
| destroy | DELETE /comisiones-cuenta/{comisionCuenta} | Desactiva (borrado lógico): `activa=false`, `vigente_hasta=today`. | — | `JsonResponse { data }` |

---

### `App\Http\Controllers\Api\V1\Configuracion\ComisionMetodoPagoController`
- **Rutas**: `/api/v1/configuracion/comisiones-metodo-pago` (apiResource, parámetro `comisionMetodoPago`)
- **Middleware**: `auth:sanctum` + `role:admin|super_admin`

| Método | Endpoint | Propósito | Request | Response |
|---|---|---|---|---|
| index | GET /comisiones-metodo-pago | Lista paginada (50) con cuenta, moneda. Filtro opcional: `activa`. | `Request` | `JsonResponse { data }` |
| store | POST /comisiones-metodo-pago | Crear comisión por método de pago. Retorna 201. | `StoreComisionMetodoPagoRequest` | `JsonResponse { data }` (201) |
| show | GET /comisiones-metodo-pago/{comisionMetodoPago} | Detalle con relaciones. | — | `JsonResponse { data }` |
| update | PUT /comisiones-metodo-pago/{comisionMetodoPago} | Actualizar comisión. | `StoreComisionMetodoPagoRequest` | `JsonResponse { data }` |
| destroy | DELETE /comisiones-metodo-pago/{comisionMetodoPago} | Desactiva: `activa=false`, `vigente_hasta=today`. | — | `JsonResponse { data }` |

---

### `App\Http\Controllers\Api\V1\Configuracion\ComisionOperadorController`
- **Rutas**: `/api/v1/configuracion/comisiones-operador` (apiResource, parámetro `comisionOperador`)
- **Middleware**: `auth:sanctum` + `role:admin|super_admin`

| Método | Endpoint | Propósito | Request | Response |
|---|---|---|---|---|
| index | GET /comisiones-operador | Lista paginada (50) con titular, tipoOperacion, moneda. Filtros: `titular_id`, `activa`. | `Request` | `JsonResponse { data }` |
| store | POST /comisiones-operador | Crear comisión por operador. Retorna 201 con relaciones. | `StoreComisionOperadorRequest` | `JsonResponse { data }` (201) |
| show | GET /comisiones-operador/{comisionOperador} | Detalle con relaciones. | — | `JsonResponse { data }` |
| update | PUT /comisiones-operador/{comisionOperador} | Actualizar comisión. | `StoreComisionOperadorRequest` | `JsonResponse { data }` |
| destroy | DELETE /comisiones-operador/{comisionOperador} | Desactiva: `activa=false`, `vigente_hasta=today`. | — | `JsonResponse { data }` |

---

### `App\Http\Controllers\Api\V1\Configuracion\ComisionOperacionController`
- **Rutas**: `/api/v1/operaciones/{operacion}/comisiones` (grupo)
- **Middleware**: `auth:sanctum`
- **Inyección**: `CalculadorComisionesService`

| Método | Endpoint | Propósito | Request | Roles |
|---|---|---|---|---|
| index | GET /operaciones/{operacion}/comisiones | Lista comisiones aplicadas a una operación, ordenadas por tipo, con moneda, origen, movimiento, editadaPor. | — | `admin\|super_admin\|contador` |
| update | PATCH /operaciones/{operacion}/comisiones/{comision} | Edita una comisión específica vía `CalculadorComisionesService::editarComision()`. Requiere `monto`, `monto_usd_equivalente`, `descripcion`, `razon_edicion`. Aborta 404 si comisión no pertenece a la operación. | `UpdateComisionOperacionRequest` | `admin\|super_admin` |

---

## Resumen de controladores analizados

| # | Controlador | Namespace | Métodos totales | Endpoints |
|---|---|---|---|---|
| 1 | Controller | App\Http\Controllers | — | — (base) |
| 2 | AuthController | App\Http\Controllers | 3 | login, logout, me |
| 3 | ClienteController | App\Http\Controllers | 8 | index, store, show, update, destroy, cuentas, operaciones, exportarOperaciones, restaurar |
| 4 | CuentaController | App\Http\Controllers | 6 | index, store, show, update, destroy, cargarSaldo |
| 5 | BancoController | App\Http\Controllers | 5 | index, store, show, update, destroy |
| 6 | CategoriaGastoController | App\Http\Controllers | 5 | index, store, show, update, destroy |
| 7 | MonedaController | App\Http\Controllers | 5 | index, store, show, update, destroy |
| 8 | TitularController | App\Http\Controllers | 5 | index, store, show, update, destroy |
| 9 | TasasController | App\Http\Controllers | 2 | actuales, historico |
| 10 | OperacionController | Api\V1 | 6 | index, store, show, update, verificar, destroy |
| 11 | PoolController | Api\V1 | 6 | index, misOrdenes, tomar, soltar, marcarPagada, cancelar |
| 12 | DashboardController | Api\V1 | 3 | general, tasasReferencia, resumen |
| 13 | GastoController | Api\V1 | 3 | index, store, show |
| 14 | ReporteComisionesController | Api\V1 | 3 | index, exportar, historico |
| 15 | UserController | Api\V1 | 4 | index, store, update, destroy |
| 16 | TasaDiariaController | Api\V1\Configuracion | 4 | index, store, vigentes, historial |
| 17 | ComisionCuentaController | Api\V1\Configuracion | 5 | index, store, show, update, destroy |
| 18 | ComisionMetodoPagoController | Api\V1\Configuracion | 5 | index, store, show, update, destroy |
| 19 | ComisionOperadorController | Api\V1\Configuracion | 5 | index, store, show, update, destroy |
| 20 | ComisionOperacionController | Api\V1\Configuracion | 2 | index, update |