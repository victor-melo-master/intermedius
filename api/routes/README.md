# routes/ — Route Definitions

| Archivo | Propósito |
|---|---|
| `api.php` | Rutas API (prefix `/api/v1`) con middleware `api`, `auth:sanctum`, roles |
| `console.php` | Definiciones de schedule para Laravel (jobs programados) |
| `web.php` | Rutas web (vacío, sin uso actual) |

## api.php — Estructura

```
/api/v1
├── /auth (login, logout, me)  →  AuthController
├── /titulares                  →  TitularController
├── /bancos                     →  BancoController
├── /monedas                    →  MonedaController
├── /cuentas                    →  CuentaController
├── /clientes                   →  ClienteController
├── /categorias-gasto           →  CategoriaGastoController
├── /tasas (actuales, historico) →  TasasController
├── /operaciones                →  OperacionController
├── /gastos                     →  GastoController
├── /pool                       →  PoolController
├── /dashboard                  →  DashboardController
├── /users                      →  UserController
├── /reportes/comisiones        →  ReporteComisionesController
└── /configuracion/             →  (5 sub-recursos de configuración)
```

## console.php — Schedule

| Job | Frecuencia |
|---|---|
| SincronizarTasasJob | Cada 1 minuto |
| SincronizarTasasReferenciaJob | Cada 1 minuto |
| AlertarTasasFaltantesJob | Diario 08:00, 14:00 |
| GenerarReporteMensualComisionesJob | 1er día del mes 06:00 |
| AutoArchivarClientesInactivos | Domingo 03:00 |
