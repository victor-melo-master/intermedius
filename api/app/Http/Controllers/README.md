# Controllers/

## Web Controllers (raíz)

| Controller | Endpoints | Descripción |
|---|---|---|
| `AuthController` | `POST /auth/login`, `POST /auth/logout`, `GET /auth/me` | Autenticación Sanctum |
| `TitularController` | CRUD `/titulares` | Titulares (cuentahabientes) |
| `BancoController` | CRUD `/bancos` | Catálogo de bancos |
| `MonedaController` | CRUD `/monedas` | Catálogo de monedas |
| `CuentaController` | CRUD `/cuentas`, `POST /cuentas/{cuenta}/saldo` | Cuentas bancarias |
| `ClienteController` | CRUD `/clientes`, `GET /clientes/{id}/cuentas`, `GET /clientes/{id}/operaciones`, `POST /clientes/{id}/operaciones/exportar`, `POST /clientes/{id}/restaurar` | Clientes + operaciones + export PDF + soft-delete restore |
| `CategoriaGastoController` | CRUD `/categorias-gasto` | Categorías de gastos |
| `TasasController` | `GET /tasas/actuales`, `GET /tasas/historico` | Tasas de mercado en tiempo real |

## API V1 Controllers (`Api/V1/`)

| Controller | Endpoints | Descripción |
|---|---|---|
| `OperacionController` | CRUD `/operaciones`, `PATCH /operaciones/{id}/verificar` | Operaciones de depósito/retiro/conversión |
| `PoolController` | `GET /pool`, `GET /pool/mis-ordenes`, `POST /pool/{id}/tomar`, `POST /pool/{id}/soltar`, `POST /pool/{id}/pagar`, `POST /pool/{id}/cancelar` | Pool de pagadores FIFO |
| `DashboardController` | `GET /dashboard/general`, `GET /dashboard/tasas-referencia`, `GET /dashboard/resumen` | Estadísticas y resúmenes |
| `GastoController` | CRUD `/gastos` | Gastos operativos |
| `ReporteComisionesController` | `GET /reportes/comisiones`, `POST /reportes/comisiones/exportar`, `GET /reportes/comisiones/historico` | Reportes de comisiones |
| `UserController` | CRUD `/users` | Gestión de usuarios (admin) |

## Configuración Controllers (`Api/V1/Configuracion/`)

| Controller | Descripción |
|---|---|
| `TasaDiariaController` | Publicar/consultar tasas diarias |
| `ComisionCuentaController` | Comisiones por cuenta |
| `ComisionMetodoPagoController` | Comisiones por método de pago |
| `ComisionOperadorController` | Comisiones por operador |
| `ComisionOperacionController` | Editar comisión en operación específica |
