# Routes — AI Context

## api.php

Todas las rutas bajo prefix `/api/v1`.

---

### Autenticación

| Método | URI | Controlador | Middleware |
|---|---|---|---|
| POST | /auth/login | AuthController@login | público |
| POST | /auth/logout | AuthController@logout | auth:sanctum |
| GET | /auth/me | AuthController@me | auth:sanctum |

---

### Catálogos (CRUD apiResource)

Cada `apiResource` expone: `GET /index`, `POST /store`, `GET /{id}`, `PUT /{id}`, `DELETE /{id}`.

| # | Recurso | Controlador | Permiso mínimo *view* | Permiso mínimo *create/update/delete* | Binding / notas |
|---|---|---|---|---|---|
| 1 | `/titulares` | TitularController | auth:sanctum | auth:sanctum | — |
| 2 | `/bancos` | BancoController | auth:sanctum | auth:sanctum | — |
| 3 | `/monedas` | MonedaController | auth:sanctum | auth:sanctum | — |
| 4 | `/cuentas` | CuentaController | auth:sanctum | auth:sanctum | — |
| 5 | `/clientes` | ClienteController | auth:sanctum | auth:sanctum | — |
| 6 | `/categorias-gasto` | CategoriaGastoController | auth:sanctum | auth:sanctum | `parameters(['categorias-gasto' => 'categoria_gasto'])` → bind `{categoria_gasto}` |

#### Rutas adicionales de cuentas

| Método | URI | Controlador | Middleware |
|---|---|---|---|
| POST | /cuentas/{cuenta}/saldo | CuentaController@cargarSaldo | auth:sanctum + role:admin\|super_admin |

#### Rutas adicionales de clientes

| Método | URI | Controlador | Middleware |
|---|---|---|---|
| GET | /clientes/{cliente}/cuentas | ClienteController@cuentas | auth:sanctum |
| GET | /clientes/{cliente}/operaciones | ClienteController@operaciones | auth:sanctum |
| POST | /clientes/{cliente}/operaciones/exportar | ClienteController@exportarOperaciones | auth:sanctum |
| POST | /clientes/{cliente}/restaurar | ClienteController@restaurar | auth:sanctum + role:admin\|super_admin |

---

### Tasas de mercado

| Método | URI | Controlador | Middleware |
|---|---|---|---|
| GET | /tasas/actuales | TasasController@actuales | auth:sanctum |
| GET | /tasas/historico | TasasController@historico | auth:sanctum |

---

### Comisiones aplicadas por operación

| Método | URI | Controlador | Middleware |
|---|---|---|---|
| GET | /operaciones/{operacion}/comisiones | ComisionOperacionController@index | auth:sanctum + role:admin\|super_admin\|contador |
| PATCH | /operaciones/{operacion}/comisiones/{comision} | ComisionOperacionController@update | auth:sanctum + role:admin\|super_admin |

---

### Operaciones (ledger contable)

| Método | URI | Controlador | Middleware | Binding |
|---|---|---|---|---|
| GET | /operaciones | OperacionController@index | auth:sanctum | `{operacion}` |
| POST | /operaciones | OperacionController@store | auth:sanctum | — |
| GET | /operaciones/{operacion} | OperacionController@show | auth:sanctum | `{operacion}` |
| PUT | /operaciones/{operacion} | OperacionController@update | auth:sanctum | `{operacion}` |
| DELETE | /operaciones/{operacion} | OperacionController@destroy | auth:sanctum | `{operacion}` (explicita) |
| PATCH | /operaciones/{operacion}/verificar | OperacionController@verificar | auth:sanctum | `{operacion}` |

---

### Pool de pagadores

| Método | URI | Controlador | Middleware |
|---|---|---|---|
| GET | /pool | PoolController@index | auth:sanctum + role:pagador\|admin\|super_admin |
| GET | /pool/mis-ordenes | PoolController@misOrdenes | auth:sanctum + role:pagador\|admin\|super_admin |
| POST | /pool/{operacion}/tomar | PoolController@tomar | auth:sanctum + role:pagador\|admin\|super_admin |
| POST | /pool/{operacion}/soltar | PoolController@soltar | auth:sanctum + role:pagador\|admin\|super_admin |
| POST | /pool/{operacion}/pagar | PoolController@marcarPagada | auth:sanctum + role:pagador\|admin\|super_admin |
| POST | /pool/{operacion}/cancelar | PoolController@cancelar | auth:sanctum + role:admin\|super_admin |

---

### Gastos (subtipo de operaciones)

| Método | URI | Controlador | Middleware |
|---|---|---|---|
| GET | /gastos | GastoController@index | auth:sanctum |
| POST | /gastos | GastoController@store | auth:sanctum |
| GET | /gastos/{operacion} | GastoController@show | auth:sanctum |

---

### Configuración

#### Tasas vigentes y diarias (lectura pública autenticada)

| Método | URI | Controlador | Middleware |
|---|---|---|---|
| GET | /configuracion/tasas-vigentes | TasaDiariaController@vigentes | auth:sanctum |
| GET | /configuracion/tasas-diarias | TasaDiariaController@index | auth:sanctum |
| GET | /configuracion/tasas-diarias/historial/{base}/{cotizada} | TasaDiariaController@historial | auth:sanctum |

#### Tasas diarias (escritura solo admin)

| Método | URI | Controlador | Middleware |
|---|---|---|---|
| POST | /configuracion/tasas-diarias | TasaDiariaController@store | auth:sanctum + role:admin\|super_admin |

#### Comisiones cuenta (apiResource — solo admin)

Binding: `parameters(['comisiones-cuenta' => 'comisionCuenta'])`.

| Método | URI | Controlador | Middleware |
|---|---|---|---|
| GET | /configuracion/comisiones-cuenta | ComisionCuentaController@index | auth:sanctum + role:admin\|super_admin |
| POST | /configuracion/comisiones-cuenta | ComisionCuentaController@store | auth:sanctum + role:admin\|super_admin |
| GET | /configuracion/comisiones-cuenta/{comisionCuenta} | ComisionCuentaController@show | auth:sanctum + role:admin\|super_admin |
| PUT | /configuracion/comisiones-cuenta/{comisionCuenta} | ComisionCuentaController@update | auth:sanctum + role:admin\|super_admin |
| DELETE | /configuracion/comisiones-cuenta/{comisionCuenta} | ComisionCuentaController@destroy | auth:sanctum + role:admin\|super_admin |

#### Comisiones operador (apiResource — solo admin)

Binding: `parameters(['comisiones-operador' => 'comisionOperador'])`.

| Método | URI | Controlador | Middleware |
|---|---|---|---|
| GET | /configuracion/comisiones-operador | ComisionOperadorController@index | auth:sanctum + role:admin\|super_admin |
| POST | /configuracion/comisiones-operador | ComisionOperadorController@store | auth:sanctum + role:admin\|super_admin |
| GET | /configuracion/comisiones-operador/{comisionOperador} | ComisionOperadorController@show | auth:sanctum + role:admin\|super_admin |
| PUT | /configuracion/comisiones-operador/{comisionOperador} | ComisionOperadorController@update | auth:sanctum + role:admin\|super_admin |
| DELETE | /configuracion/comisiones-operador/{comisionOperador} | ComisionOperadorController@destroy | auth:sanctum + role:admin\|super_admin |

#### Comisiones método de pago (apiResource — solo admin)

Binding: `parameters(['comisiones-metodo-pago' => 'comisionMetodoPago'])`.

| Método | URI | Controlador | Middleware |
|---|---|---|---|
| GET | /configuracion/comisiones-metodo-pago | ComisionMetodoPagoController@index | auth:sanctum + role:admin\|super_admin |
| POST | /configuracion/comisiones-metodo-pago | ComisionMetodoPagoController@store | auth:sanctum + role:admin\|super_admin |
| GET | /configuracion/comisiones-metodo-pago/{comisionMetodoPago} | ComisionMetodoPagoController@show | auth:sanctum + role:admin\|super_admin |
| PUT | /configuracion/comisiones-metodo-pago/{comisionMetodoPago} | ComisionMetodoPagoController@update | auth:sanctum + role:admin\|super_admin |
| DELETE | /configuracion/comisiones-metodo-pago/{comisionMetodoPago} | ComisionMetodoPagoController@destroy | auth:sanctum + role:admin\|super_admin |

---

### Reportes de comisiones

| Método | URI | Controlador | Middleware |
|---|---|---|---|
| GET | /reportes/comisiones-operadores | ReporteComisionesController@index | auth:sanctum + role:admin\|super_admin\|contador |
| POST | /reportes/comisiones-operadores/exportar | ReporteComisionesController@exportar | auth:sanctum + role:admin\|super_admin\|contador |
| GET | /reportes/comisiones-operadores/historico | ReporteComisionesController@historico | auth:sanctum + role:admin\|super_admin\|contador |

---

### Gestión de usuarios

| Método | URI | Controlador | Middleware |
|---|---|---|---|
| GET | /usuarios | UserController@index | auth:sanctum + role:admin\|super_admin |
| POST | /usuarios | UserController@store | auth:sanctum + role:admin\|super_admin |
| GET | /usuarios/{user} | UserController@show | auth:sanctum + role:admin\|super_admin |
| PUT | /usuarios/{user} | UserController@update | auth:sanctum + role:admin\|super_admin |
| DELETE | /usuarios/{user} | UserController@destroy | auth:sanctum + role:admin\|super_admin |

---

### Bitácora (Activity Log)

| Método | URI | Middleware | Comportamiento |
|---|---|---|---|
| GET | /admin/bitacora | auth:sanctum + role:super_admin | Closure: consulta `Activity` ordenado por `created_at DESC`. Filtros opcionales: `modelo` (LIKE sobre `subject_type`), `user_id` (causer_id), `desde` / `hasta` (rango fecha). Pagina 50 resultados. |

---

### Dashboard

| Método | URI | Controlador | Middleware |
|---|---|---|---|
| GET | /dashboard/general | DashboardController@general | auth:sanctum |
| GET | /dashboard/tasas-referencia | DashboardController@tasasReferencia | auth:sanctum |
| GET | /dashboard/resumen | DashboardController@resumen | auth:sanctum |

---

### Resumen de permisos por rol

| Rol | Alcance |
|---|---|
| **super_admin** | Todo (bitácora incluida) |
| **admin** | Todo excepto bitácora |
| **contador** | Reportes de comisiones + comisiones por operación (lectura) |
| **pagador** | Pool de pagadores (excepto cancelar) |
| **autenticado** (sin role adicional) | Catálogos, tasas, operaciones, gastos, clientes, dashboard |

---

## console.php

| Job / Comando | Frecuencia | Sin solapamiento | Nombre |
|---|---|---|---|
| `SincronizarTasasJob` | cada minuto | sí | `sincronizar-tasas` |
| `SincronizarTasasReferenciaJob` | cada minuto | sí | `sincronizar-tasas-referencia` |
| `AlertarTasasFaltantesJob` | diario a las 08:00 | no | `alertar-tasas-faltantes-manana` |
| `AlertarTasasFaltantesJob` | diario a las 14:00 | no | `alertar-tasas-faltantes-tarde` |
| `GenerarReporteMensualComisionesJob` | 1.er día del mes a las 06:00 | sí | `reporte-mensual-comisiones` |
| `AutoArchivarClientesInactivos` | domingo a las 03:00 | sí | `auto-archivar-clientes-inactivos` |
| `inspire` (Artisan command) | cada hora | no | — |
