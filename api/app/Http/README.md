# app/Http/ — HTTP Layer

```
Http/
├── Controllers/    # Controladores de API (web + API)
├── Requests/       # Form Request validation
└── Resources/      # JSON resource transformations
```

## Controllers

- **Base**: `Controller.php` — trait `AuthorizesRequests, ValidatesRequests`
- **Catálogos**: `TitularController`, `BancoController`, `MonedaController`, `CuentaController`, `ClienteController`, `CategoriaGastoController` — CRUD estándar
- **Auth**: `AuthController` — login/logout/me via Sanctum
- **Tasas**: `TasasController` — tasas actuales e históricas
- **Api/V1/**: `OperacionController`, `PoolController`, `DashboardController`, `GastoController`, `ReporteComisionesController`, `UserController`
- **Api/V1/Configuracion/**: `TasaDiariaController`, `ComisionCuentaController`, `ComisionMetodoPagoController`, `ComisionOperadorController`, `ComisionOperacionController`

## Requests

Ubicados por entidad en subdirectorios. Cada entidad tiene `Store*Request` (creación) y `Update*Request` (actualización). Ubicaciones especiales como `Auth/LoginRequest` y `Operacion/VerificarOperacionRequest`.

## Resources

- `OperacionResource.php` — Transformación de operaciones con relaciones eager-loaded
- `MovimientoResource.php` — Transformación de movimientos con cuenta y moneda
