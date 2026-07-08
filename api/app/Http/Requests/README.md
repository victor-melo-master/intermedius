# Requests/ — Form Request Validation

Cada entidad tiene su propio subdirectorio con dos clases: `Store{Entidad}Request` (creación) y `Update{Entidad}Request` (actualización).

| Subdirectorio | Archivos | Entidad validada |
|---|---|---|
| `Auth/` | `LoginRequest` | Credenciales de login |
| `Banco/` | `StoreBancoRequest`, `UpdateBancoRequest` | Bancos |
| `CategoriaGasto/` | `StoreCategoriaGastoRequest`, `UpdateCategoriaGastoRequest` | Categorías de gasto |
| `Cliente/` | `StoreClienteRequest`, `UpdateClienteRequest` | Clientes |
| `Configuracion/` | `StoreComisionCuentaRequest`, `StoreComisionMetodoPagoRequest`, `StoreComisionOperadorRequest`, `StoreTasaDiariaRequest`, `UpdateComisionOperacionRequest` | Configuraciones |
| `Cuenta/` | `StoreCuentaRequest`, `UpdateCuentaRequest` | Cuentas bancarias |
| `Gasto/` | `StoreGastoRequest` | Gastos operativos |
| `Moneda/` | `StoreMonedaRequest`, `UpdateMonedaRequest` | Monedas |
| `Operacion/` | `StoreOperacionRequest`, `UpdateOperacionRequest`, `VerificarOperacionRequest` | Operaciones |
| `Titular/` | `StoreTitularRequest`, `UpdateTitularRequest` | Titulares |

## Convenciones

- `authorize()` delega en Policy del modelo
- `rules()` define validación de tipo + formato
- Métodos `withValidator()` / `prepareForValidation()` para lógica condicional (ej: efectivo sin titular asigna a Terceros)
