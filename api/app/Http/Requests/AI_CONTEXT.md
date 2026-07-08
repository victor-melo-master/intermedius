# Form Requests — AI Context

## `App\Http\Requests\Auth\LoginRequest`
- **Endpoint**: POST /api/v1/auth/login
- **Autorización**: pública (`return true`)

| Campo | Reglas |
|---|---|
| email | required, email |
| password | required, string |

---

## `App\Http\Requests\Banco\StoreBancoRequest`
- **Endpoint**: POST /api/v1/bancos
- **Autorización**: `$user->can('create', App\Models\Banco::class)`

| Campo | Reglas |
|---|---|
| nombre | required, string, max:255, unique:bancos,nombre |
| codigo | nullable, string, max:20 |
| pais | nullable, string, size:2 |
| activo | boolean |

---

## `App\Http\Requests\Banco\UpdateBancoRequest`
- **Endpoint**: PUT|PATCH /api/v1/bancos/{banco}
- **Autorización**: `$user->can('update', $this->route('banco'))`

| Campo | Reglas |
|---|---|
| nombre | sometimes, string, max:255, unique:bancos,nombre (ignora el propio `{banco}`) |
| codigo | nullable, string, max:20 |
| pais | nullable, string, size:2 |
| activo | boolean |

---

## `App\Http\Requests\CategoriaGasto\StoreCategoriaGastoRequest`
- **Endpoint**: POST /api/v1/categorias-gasto
- **Autorización**: `$user->can('create', App\Models\CategoriaGasto::class)`

| Campo | Reglas |
|---|---|
| nombre | required, string, max:100, unique:categorias_gasto,nombre |
| titular_id | nullable, integer, exists:titulares,id |
| activa | boolean |

---

## `App\Http\Requests\CategoriaGasto\UpdateCategoriaGastoRequest`
- **Endpoint**: PUT|PATCH /api/v1/categorias-gasto/{categoria_gasto}
- **Autorización**: `$user->can('update', $this->route('categoria_gasto'))`

| Campo | Reglas |
|---|---|
| nombre | sometimes, string, max:100, unique:categorias_gasto,nombre (ignora el propio `{categoria_gasto}`) |
| titular_id | nullable, integer, exists:titulares,id |
| activa | boolean |

---

## `App\Http\Requests\Cliente\StoreClienteRequest`
- **Endpoint**: POST /api/v1/clientes
- **Autorización**: `$user->can('create', App\Models\Cliente::class)`

| Campo | Reglas |
|---|---|
| nombre | required, string, max:255 |
| alias | nullable, string, max:100 |
| documento | nullable, string, max:50 |
| telefono | nullable, string, max:30 |
| email | nullable, email, max:255 |
| notas | nullable, string |
| activo | boolean |

---

## `App\Http\Requests\Cliente\UpdateClienteRequest`
- **Endpoint**: PUT|PATCH /api/v1/clientes/{cliente}
- **Autorización**: `$user->can('update', $this->route('cliente'))`

| Campo | Reglas |
|---|---|
| nombre | sometimes, string, max:255 |
| alias | nullable, string, max:100 |
| documento | nullable, string, max:50 |
| telefono | nullable, string, max:30 |
| email | nullable, email, max:255 |
| notas | nullable, string |
| activo | boolean |

---

## `App\Http\Requests\Configuracion\StoreComisionCuentaRequest`
- **Endpoint**: POST /api/v1/configuracion/comisiones-cuenta (role:admin|super_admin)
- **Autorización**: `$user->hasRole(['admin', 'super_admin'])`

| Campo | Reglas |
|---|---|
| cuenta_id | nullable, integer, exists:cuentas,id |
| banco_id | nullable, integer, exists:bancos,id |
| descripcion | required, string, max:100 |
| tipo_calculo | required, in:porcentaje,monto_fijo |
| valor | required, numeric, gt:0 |
| moneda_id | required, integer, exists:monedas,id |
| aplica_a | required, in:ingreso,egreso,ambos |
| vigente_desde | required, date |
| vigente_hasta | nullable, date, after_or_equal:vigente_desde |
| activa | sometimes, boolean |

**Reglas de negocio extra (`withValidator`)**:
- Debe especificar al menos `cuenta_id` o `banco_id` (si ambos están vacíos, se agrega error a `cuenta_id`).

---

## `App\Http\Requests\Configuracion\StoreComisionMetodoPagoRequest`
- **Endpoint**: POST /api/v1/configuracion/comisiones-metodo-pago (role:admin|super_admin)
- **Autorización**: `$user->hasRole(['admin', 'super_admin'])`

| Campo | Reglas |
|---|---|
| nombre_metodo | required, string, max:80 |
| cuenta_id | nullable, integer, exists:cuentas,id |
| descripcion | required, string, max:100 |
| tipo_calculo | required, in:porcentaje,monto_fijo |
| valor | required, numeric, gt:0 |
| moneda_id | required, integer, exists:monedas,id |
| vigente_desde | required, date |
| vigente_hasta | nullable, date, after_or_equal:vigente_desde |
| activa | sometimes, boolean |

---

## `App\Http\Requests\Configuracion\StoreComisionOperadorRequest`
- **Endpoint**: POST /api/v1/configuracion/comisiones-operador (role:admin|super_admin)
- **Autorización**: `$user->hasRole(['admin', 'super_admin'])`

| Campo | Reglas |
|---|---|
| titular_id | required, integer, exists:titulares,id |
| tipo_operacion_id | nullable, integer, exists:tipos_operacion,id |
| descripcion | required, string, max:100 |
| tipo_calculo | required, in:porcentaje,monto_fijo |
| valor | required, numeric, gt:0 |
| moneda_id | required, integer, exists:monedas,id |
| base_calculo | sometimes, in:monto_operacion,ganancia_bruta |
| vigente_desde | required, date |
| vigente_hasta | nullable, date, after_or_equal:vigente_desde |
| activa | sometimes, boolean |

---

## `App\Http\Requests\Configuracion\StoreTasaDiariaRequest`
- **Endpoint**: POST /api/v1/configuracion/tasas-diarias (role:admin|super_admin)
- **Autorización**: `$user->hasRole(['admin', 'super_admin'])`

| Campo | Reglas |
|---|---|
| fecha | required, date |
| moneda_base_id | required, integer, exists:monedas,id |
| moneda_cotizada_id | required, integer, exists:monedas,id, different:moneda_base_id |
| tasa_compra | required, numeric, gt:0 |
| tasa_compra_minima | nullable, numeric, gt:0 |
| tasa_venta | required, numeric, gt:0 |
| tasa_venta_minima | nullable, numeric, gt:0 |
| notas | nullable, string, max:500 |

**Reglas de negocio extra (`withValidator`)**:
- Si `tasa_venta < tasa_compra`, se exige que `notas` tenga al menos 10 caracteres para justificar la excepción.

---

## `App\Http\Requests\Configuracion\UpdateComisionOperacionRequest`
- **Endpoint**: PATCH /api/v1/operaciones/{operacion}/comisiones/{comision} (role:admin|super_admin)
- **Autorización**: `$user->hasRole(['admin', 'super_admin'])`

| Campo | Reglas |
|---|---|
| razon_edicion | required, string, min:10, max:500 |
| monto | nullable, numeric, gt:0 |
| monto_usd_equivalente | nullable, numeric, gte:0 |
| descripcion | nullable, string, max:200 |

---

## `App\Http\Requests\Cuenta\StoreCuentaRequest`
- **Endpoint**: POST /api/v1/cuentas
- **Autorización**: `$user->can('create', App\Models\Cuenta::class)`

| Campo | Reglas |
|---|---|
| titular_id | nullable, integer, exists:titulares,id, required_without:cliente_id |
| cliente_id | nullable, integer, exists:clientes,id, required_without:titular_id |
| banco_id | nullable, integer, exists:bancos,id |
| moneda_id | required, integer, exists:monedas,id |
| alias | required, string, max:100, **unique compuesto**: único dentro del mismo `titular_id` o `cliente_id` (usa `where` con `orWhere`) |
| tipo | required, in:banco,plataforma,cash,wallet,zelle,efectivo,otro |
| numero_cuenta | nullable, string, max:50 |
| activa | boolean |
| notas | nullable, string |

**Métodos extra**:
- `prepareForValidation()`: si `tipo === 'efectivo'` y no se envió `titular_id` ni `cliente_id`, asigna `titular_id = 1`.

---

## `App\Http\Requests\Cuenta\UpdateCuentaRequest`
- **Endpoint**: PUT|PATCH /api/v1/cuentas/{cuenta}
- **Autorización**: `$user->can('update', $this->route('cuenta'))`

| Campo | Reglas |
|---|---|
| titular_id | nullable, integer, exists:titulares,id, required_without:cliente_id |
| cliente_id | nullable, integer, exists:clientes,id, required_without:titular_id |
| banco_id | nullable, integer, exists:bancos,id |
| moneda_id | sometimes, integer, exists:monedas,id |
| alias | sometimes, string, max:100, **unique compuesto**: único dentro del mismo `titular_id`/`cliente_id`, ignorando el propio `{cuenta}` |
| tipo | sometimes, in:banco,plataforma,cash,wallet,zelle,efectivo,otro |
| numero_cuenta | nullable, string, max:50 |
| activa | boolean |
| notas | nullable, string |

**Detalle**: cuando no se envía `titular_id` o `cliente_id`, usa el valor existente del modelo `$cuenta` para construir la cláusula unique.

---

## `App\Http\Requests\Gasto\StoreGastoRequest`
- **Endpoint**: POST /api/v1/gastos
- **Autorización**: `$user->can('create', App\Models\Operacion::class)`

| Campo | Reglas |
|---|---|
| fecha | required, date, before_or_equal:today |
| categoria_gasto_id | required, integer, exists:categorias_gasto,id |
| operador_id | required, integer, exists:users,id |
| referencia | nullable, string, max:100 |
| descripcion | nullable, string |
| movimientos | required, array, min:1 |
| movimientos.*.cuenta_id | required, integer, exists:cuentas,id |
| movimientos.*.monto | required, numeric, not_in:0 |
| movimientos.*.tasa_a_usd | required, numeric, gt:0 |

**Métodos extra**:
- `validated()`: inyecta `tipo_codigo = 'gasto'` y `origen = 'manual'` (por defecto) en los datos validados.

---

## `App\Http\Requests\Moneda\StoreMonedaRequest`
- **Endpoint**: POST /api/v1/monedas
- **Autorización**: `$user->can('create', App\Models\Moneda::class)`

| Campo | Reglas |
|---|---|
| codigo | required, string, max:10, unique:monedas,codigo |
| nombre | required, string, max:100 |
| simbolo | nullable, string, max:10 |
| es_fiat | boolean |
| es_cripto | boolean |
| decimales | integer, min:0, max:18 |
| activa | boolean |

---

## `App\Http\Requests\Moneda\UpdateMonedaRequest`
- **Endpoint**: PUT|PATCH /api/v1/monedas/{moneda}
- **Autorización**: `$user->can('update', $this->route('moneda'))`

| Campo | Reglas |
|---|---|
| codigo | sometimes, string, max:10, unique:monedas,codigo (ignora el propio `{moneda}`) |
| nombre | sometimes, string, max:100 |
| simbolo | nullable, string, max:10 |
| es_fiat | boolean |
| es_cripto | boolean |
| decimales | integer, min:0, max:18 |
| activa | boolean |

---

## `App\Http\Requests\Operacion\StoreOperacionRequest`
- **Endpoint**: POST /api/v1/operaciones
- **Autorización**: `$user->can('create', App\Models\Operacion::class)`

| Campo | Reglas |
|---|---|
| fecha | required, date, before_or_equal:today |
| tipo_codigo | required, string, exists:tipos_operacion,codigo |
| cliente_id | nullable, integer, exists:clientes,id |
| cliente_emisor_id | nullable, integer, exists:clientes,id, required_if:tipo_codigo,intermediada |
| cliente_receptor_id | nullable, integer, exists:clientes,id, required_if:tipo_codigo,intermediada |
| categoria_gasto_id | nullable, integer, exists:categorias_gasto,id |
| operador_id | required, integer, exists:users,id |
| tasa_aplicada | nullable, numeric, min:0 |
| tasa_compra | nullable, numeric, min:0, required_if:tipo_codigo,intermediada |
| tasa_venta | nullable, numeric, min:0, required_if:tipo_codigo,intermediada |
| genera_comision | nullable, boolean |
| monto_comision | nullable, numeric, min:0 |
| tipo_comision | nullable, in:pago_movil,otros_bancos,mismo_banco,manual |
| tasa_mercado_snapshot | nullable, numeric, min:0 |
| fuente_tasa_mercado | nullable, string, max:30 |
| referencia | nullable, string, max:100 |
| descripcion | nullable, string |
| origen | nullable, in:manual,importado,ajuste_apertura |
| origen_referencia | nullable, string, max:100, unique:operaciones,origen_referencia |
| movimientos | required, array, **regla personalizada**: `tipo_codigo === 'intermediada'` → min:4, sino → min:2 |
| movimientos.*.cuenta_id | required, integer, exists:cuentas,id |
| movimientos.*.monto | required, numeric, not_in:0 |
| movimientos.*.tasa_a_usd | nullable, numeric, gt:0 |

**Reglas de negocio extra (`withValidator`)**:
- Si `tipo_codigo === 'gasto'`, `categoria_gasto_id` es obligatorio (se agrega error si está vacío).
- `operador_id` debe coincidir con el usuario autenticado, salvo que tenga rol `super_admin`.
- Si `tipo_codigo === 'intermediada'`:
  - `tasa_venta` debe ser > `tasa_compra`.
  - `cliente_emisor_id` y `cliente_receptor_id` no pueden ser iguales.

---

## `App\Http\Requests\Operacion\UpdateOperacionRequest`
- **Endpoint**: PUT|PATCH /api/v1/operaciones/{operacion}
- **Autorización**: `$user->hasRole('super_admin') || $user->hasRole('admin') || $operacion->operador_id === $user->id || $operacion->pagador_id === $user->id`

| Campo | Reglas |
|---|---|
| fecha | sometimes, date, before_or_equal:today |
| tipo_codigo | sometimes, string, exists:tipos_operacion,codigo |
| cliente_id | nullable, integer, exists:clientes,id |
| categoria_gasto_id | nullable, integer, exists:categorias_gasto,id |
| operador_id | sometimes, integer, exists:users,id |
| tasa_aplicada | sometimes, numeric, min:0 |
| genera_comision | nullable, boolean |
| monto_comision | nullable, numeric, min:0 |
| tipo_comision | nullable, in:pago_movil,otros_bancos,mismo_banco,manual |
| tasa_mercado_snapshot | nullable, numeric, min:0 |
| fuente_tasa_mercado | nullable, string, max:30 |
| referencia | nullable, string, max:100 |
| descripcion | nullable, string |
| motivo_edicion | required, string, max:500 |
| movimientos | sometimes, array, min:2 |
| movimientos.*.cuenta_id | required, integer, exists:cuentas,id |
| movimientos.*.monto | required, numeric, not_in:0 |
| movimientos.*.tasa_a_usd | nullable, numeric, gt:0 |

**Reglas de negocio extra (`withValidator`)**:
- No se puede editar una operación con `estatus === 'verificado'` (salvo `super_admin`).
- No se puede editar una operación con `estado_pool === 'cancelada'`.
- `operador_id` solo puede cambiarlo un `super_admin`.

**Mensajes personalizados (`messages()`)**:
| Clave | Mensaje |
|---|---|
| motivo_edicion.required | Debe indicar el motivo de la edición. |
| motivo_edicion.max | El motivo no puede exceder los 500 caracteres. |

---

## `App\Http\Requests\Operacion\VerificarOperacionRequest`
- **Endpoint**: PATCH /api/v1/operaciones/{operacion}/verificar
- **Autorización**: `$user->can('verificar', $this->route('operacion'))`
- **Rules**: `[]` (vacío, solo autorización)

---

## `App\Http\Requests\Titular\StoreTitularRequest`
- **Endpoint**: POST /api/v1/titulares
- **Autorización**: `$user->can('create', App\Models\Titular::class)`

| Campo | Reglas |
|---|---|
| nombre | required, string, max:255, unique:titulares,nombre |
| alias | nullable, string, max:100 |
| activo | boolean |

---

## `App\Http\Requests\Titular\UpdateTitularRequest`
- **Endpoint**: PUT|PATCH /api/v1/titulares/{titular}
- **Autorización**: `$user->can('update', $this->route('titular'))`

| Campo | Reglas |
|---|---|
| nombre | sometimes, string, max:255, unique:titulares,nombre (ignora el propio `{titular}`) |
| alias | nullable, string, max:100 |
| activo | boolean |
