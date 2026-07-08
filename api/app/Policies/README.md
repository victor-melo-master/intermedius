# Policies/ — Authorization Policies

Cada entidad tiene su Policy, autorizando acciones según roles Spatie.

| Policy | Entidad | Acciones |
|---|---|---|
| `BancoPolicy` | `Banco` | CRUD completo |
| `CategoriaGastoPolicy` | `CategoriaGasto` | CRUD completo |
| `ClientePolicy` | `Cliente` | CRUD + `restore` (soft-delete) |
| `CuentaPolicy` | `Cuenta` | CRUD + `cargarSaldo` |
| `MonedaPolicy` | `Moneda` | CRUD completo |
| `OperacionPolicy` | `Operacion` | CRUD + `verificar` |
| `TitularPolicy` | `Titular` | CRUD completo |

## Reglas generales

- `viewAny` / `view`: `admin | operador | contador | lectura`
- `create` / `update` / `delete`: `admin`
- `super_admin` siempre autorizado (Spatie `Gate::before`)
