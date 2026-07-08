# Models/ — Eloquent ORM

16 modelos que reflejan el esquema de base de datos.

## Catálogos

| Modelo | Tabla | Descripción |
|---|---|---|
| `Banco` | `bancos` | Bancos registrados |
| `Moneda` | `monedas` | Monedas (USD, VES, USDT, etc.) |
| `TipoOperacion` | `tipos_operacion` | Tipos de operación (compra, venta, intermediada, etc.) |
| `CategoriaGasto` | `categorias_gasto` | Categorías de gastos operativos |
| `Titular` | `titulares` | Cuentahabientes/operadores |

## Entidades Financieras

| Modelo | Tabla | Descripción |
|---|---|---|
| `Cliente` | `clientes` | Clientes de la casa de cambio (soft-deletes) |
| `Cuenta` | `cuentas` | Cuentas bancarias (pertenecen a titular o cliente, mutuamente excluyente) |
| `Operacion` | `operaciones` | Operaciones de depósito/retiro/conversión |
| `Movimiento` | `movimientos` | Movimientos de fondos asociados a operaciones |

## Tasas

| Modelo | Tabla | Descripción |
|---|---|---|
| `TasaDiaria` | `tasas_diarias` | Tasas publicadas diariamente por el admin |
| `TasaMercado` | `tasas_mercado` | Tasas sincronizadas de fuentes externas (BCV, paralelo, Binance P2P) |

## Comisiones

| Modelo | Tabla | Descripción |
|---|---|---|
| `ComisionCuenta` | `comisiones_cuenta` | Comisiones configuradas por cuenta |
| `ComisionMetodoPago` | `comisiones_metodo_pago` | Comisiones por método de pago |
| `ComisionOperador` | `comisiones_operador` | Comisiones por operador (vigencia) |
| `ComisionOperacion` | `comisiones_operacion` | Comisiones aplicadas a operaciones específicas |

## Usuarios

| Modelo | Tabla | Descripción |
|---|---|---|
| `User` | `users` | Usuarios del sistema con roles Spatie |

## Soft Deletes

Los siguientes modelos usan `Illuminate\Database\Eloquent\SoftDeletes`:
- `Cliente`
- `Operacion` (soft-delete lógico para cancelación)
