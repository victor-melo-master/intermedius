# Policies — AI Context

Todas las policies heredan el comportamiento del método `before()`: si el usuario tiene rol `super_admin`, se concede acceso total (retorna `true`). Este método no se documenta en cada tabla para evitar repetición, pero aplica universalmente.

---

## `BancoPolicy`
- **Modelo**: `App\Models\Banco`

| Método | Lógica | Roles autorizados |
|---|---|---|
| viewAny | `$user->hasRole(['admin', 'operador', 'contador', 'lectura'])` | admin, operador, contador, lectura |
| view | `$user->hasRole(['admin', 'operador', 'contador', 'lectura'])` | admin, operador, contador, lectura |
| create | `$user->hasRole('admin')` | admin |
| update | `$user->hasRole('admin')` | admin |
| delete | `$user->hasRole('admin')` | admin |

---

## `CategoriaGastoPolicy`
- **Modelo**: `App\Models\CategoriaGasto`

| Método | Lógica | Roles autorizados |
|---|---|---|
| viewAny | `$user->hasRole(['admin', 'operador', 'contador', 'lectura'])` | admin, operador, contador, lectura |
| view | `$user->hasRole(['admin', 'operador', 'contador', 'lectura'])` | admin, operador, contador, lectura |
| create | `$user->hasRole('admin')` | admin |
| update | `$user->hasRole('admin')` | admin |
| delete | `$user->hasRole('admin')` | admin |

---

## `ClientePolicy`
- **Modelo**: `App\Models\Cliente`

| Método | Lógica | Roles autorizados |
|---|---|---|
| viewAny | `$user->hasRole(['admin', 'operador', 'contador', 'lectura'])` | admin, operador, contador, lectura |
| view | `$user->hasRole(['admin', 'operador', 'contador', 'lectura'])` | admin, operador, contador, lectura |
| create | `$user->hasRole('admin')` | admin |
| update | `$user->hasRole(['admin', 'operador'])` | admin, operador |
| delete | `$user->hasRole('admin')` | admin |
| restore | `$user->hasRole('admin')` | admin |

---

## `CuentaPolicy`
- **Modelo**: `App\Models\Cuenta`

| Método | Lógica | Roles autorizados |
|---|---|---|
| viewAny | `$user->hasRole(['admin', 'operador', 'contador', 'lectura'])` | admin, operador, contador, lectura |
| view | `$user->hasRole(['admin', 'operador', 'contador', 'lectura'])` | admin, operador, contador, lectura |
| create | Admin siempre ok. Operador solo si `request()->input('titular_id')` coincide con el id del titular con `alias = 'terceros'` | admin, operador (condicionado a titular "Terceros") |
| update | `$user->hasRole('admin')` | admin |
| delete | `$user->hasRole('admin')` | admin |

**Lógica adicional en `create`**: Se busca en DB `Titular::where('alias', 'terceros')->value('id')` y se compara con el `titular_id` enviado en la request. Si coincide, un operador puede crear la cuenta.

---

## `MonedaPolicy`
- **Modelo**: `App\Models\Moneda`

| Método | Lógica | Roles autorizados |
|---|---|---|
| viewAny | `$user->hasRole(['admin', 'operador', 'contador', 'lectura'])` | admin, operador, contador, lectura |
| view | `$user->hasRole(['admin', 'operador', 'contador', 'lectura'])` | admin, operador, contador, lectura |
| create | `$user->hasRole('admin')` | admin |
| update | `$user->hasRole('admin')` | admin |
| delete | `$user->hasRole('admin')` | admin |

---

## `OperacionPolicy`
- **Modelo**: `App\Models\Operacion`

| Método | Lógica | Roles autorizados |
|---|---|---|
| viewAny | `$user->hasRole(['admin', 'operador', 'contador', 'lectura'])` | admin, operador, contador, lectura |
| view | `$user->hasRole(['admin', 'operador', 'contador', 'lectura'])` | admin, operador, contador, lectura |
| create | `$user->hasRole(['admin', 'operador'])` | admin, operador |
| verificar | `$user->hasRole(['admin', 'contador'])` | admin, contador |

**Nota**: El método `verificar` es una ability personalizada (no es CRUD estándar) usada para aprobar/verificar una operación.

---

## `TitularPolicy`
- **Modelo**: `App\Models\Titular`

| Método | Lógica | Roles autorizados |
|---|---|---|
| viewAny | `$user->hasRole(['admin', 'operador', 'contador', 'lectura'])` | admin, operador, contador, lectura |
| view | `$user->hasRole(['admin', 'operador', 'contador', 'lectura'])` | admin, operador, contador, lectura |
| create | `$user->hasRole('admin')` | admin |
| update | `$user->hasRole('admin')` | admin |
| delete | `$user->hasRole('admin')` | admin |

---

## Roles del sistema

| Rol | Descripción implícita en policies |
|---|---|
| `super_admin` | Bypass total vía `before()` en todas las policies |
| `admin` | CRUD completo en todos los modelos |
| `operador` | Lectura en todos; create en Operacion; create condicionado en Cuenta (solo titular "Terceros"); update en Cliente |
| `contador` | Lectura en todos; `verificar` en Operacion |
| `lectura` | Solo lectura (viewAny / view) en todos los modelos |