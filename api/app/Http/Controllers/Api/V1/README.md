# Api/V1/ — API Version 1

Base URL: `/api/v1`

## Endpoints

| Método | URI | Controlador | Middleware |
|---|---|---|---|
| CRUD | `/operaciones` | `OperacionController` | `role:admin\|operador\|super_admin` |
| `PATCH` | `/operaciones/{operacion}/verificar` | `OperacionController@verificar` | `role:admin\|super_admin` |
| CRUD | `/gastos` | `GastoController` | `role:admin\|contador\|super_admin` |
| `GET` | `/pool` | `PoolController@index` | `role:admin\|pagador\|super_admin` |
| `POST` | `/pool/{operacion}/tomar` | `PoolController@tomar` | `role:admin\|pagador\|super_admin` |
| `POST` | `/pool/{operacion}/soltar` | `PoolController@soltar` | `role:admin\|pagador\|super_admin` |
| `POST` | `/pool/{operacion}/pagar` | `PoolController@marcarPagada` | `role:admin\|pagador\|super_admin` |
| `POST` | `/pool/{operacion}/cancelar` | `PoolController@cancelar` | `role:admin\|super_admin` |
| `GET` | `/dashboard/general` | `DashboardController@general` | `auth:sanctum` |
| `GET` | `/dashboard/tasas-referencia` | `DashboardController@tasasReferencia` | `auth:sanctum` |
| `GET` | `/dashboard/resumen` | `DashboardController@resumen` | `auth:sanctum` |
| CRUD | `/users` | `UserController` | `role:admin\|super_admin` |
| `GET/POST` | `/reportes/comisiones` | `ReporteComisionesController` | `role:admin\|contador\|super_admin` |

## Configuración

| URI | Controlador |
|---|---|
| `/configuracion/tasas-diarias` | `TasaDiariaController` |
| `/configuracion/comisiones-cuenta` | `ComisionCuentaController` |
| `/configuracion/comisiones-metodo-pago` | `ComisionMetodoPagoController` |
| `/configuracion/comisiones-operador` | `ComisionOperadorController` |
| `/configuracion/comisiones-operacion` | `ComisionOperacionController` |
