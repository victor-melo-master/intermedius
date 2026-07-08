# API Reference — Intermedius

**Base URL:** `/api/v1`  
**Auth:** Laravel Sanctum (Bearer token en header `Authorization: Bearer {token}`)  
**Roles:** `super_admin`, `admin`, `operador`, `contador`, `lectura`, `pagador`

---

## Autenticación

### `POST /api/v1/auth/login`
Pública.

```json
{ "email": "required|email", "password": "required|string" }
```
→ `200` `{ "token": "...", "user": { id, name, email, roles, titular_id, last_login_at } }`  
→ `401` credenciales incorrectas | `403` usuario inactivo

### `POST /api/v1/auth/logout`
`auth:sanctum` → `200` `{ "message": "Sesión cerrada correctamente." }`

### `GET /api/v1/auth/me`
`auth:sanctum` → `200` `{ id, name, email, roles, titular_id, last_login_at }`

---

## Catálogos (CRUD básico)

| Recurso | GET /index | POST /store | GET /{id} | PUT /{id} | DELETE /{id} |
|---------|-----------|-------------|-----------|-----------|---------------|
| `/titulares` | viewAny | create | view | update | delete |
| `/bancos` | viewAny | create | view | update | delete |
| `/monedas` | viewAny | create | view | update | delete |
| `/cuentas` | viewAny | create | view | update | delete |
| `/clientes` | viewAny | create | view | update | delete |
| `/categorias-gasto` | viewAny | create | view | update | delete |

**Políticas por rol:**
- `viewAny`/`view`: admin | operador | contador | lectura
- `create`/`update`/`delete`: admin (super_admin siempre pasa)

### Titulares `{titular}`

| Campo | Tipo | Reglas |
|-------|------|--------|
| nombre | string | required, max:255, unique |
| alias | string? | max:100 |
| activo | bool | — |

**GET /titulares?inactivos=true** — filtra inactivos (default: solo activos).  
**GET /titulares/{id}** — eager carga `cuentas`.

### Bancos `{banco}`

| Campo | Tipo | Reglas |
|-------|------|--------|
| nombre | string | required, max:255, unique |
| codigo | string? | max:20 |
| pais | string? | size:2 (código ISO) |
| activo | bool | — |

### Monedas `{moneda}`

| Campo | Tipo | Reglas |
|-------|------|--------|
| codigo | string | required, max:10, unique |
| nombre | string | required, max:100 |
| simbolo | string? | max:10 |
| es_fiat | bool | — |
| es_cripto | bool | — |
| decimales | int | min:0, max:18 |
| activa | bool | — |

### Cuentas `{cuenta}`

| Campo | Tipo | Reglas |
|-------|------|--------|
| titular_id | int? | exists:titulares, required_without:cliente_id |
| cliente_id | int? | exists:clientes, required_without:titular_id |
| banco_id | int? | exists:bancos |
| moneda_id | int | required, exists:monedas |
| alias | string | required, max:100, unique por owner |
| tipo | string | required, in:banco,plataforma,cash,wallet,zelle,efectivo,otro |
| numero_cuenta | string? | max:50 |
| activa | bool | — |
| notas | string? | — |

Regla de negocio: debe tener exactamente un dueño (titular XOR cliente).

**GET /cuentas?cliente_id=&titular_id=&moneda_id=** — filtra por owner/moneda.  
Eager carga siempre: `titular`, `cliente`, `banco`, `moneda`.

### Clientes `{cliente}`

| Campo | Tipo | Reglas |
|-------|------|--------|
| nombre | string | required, max:255 |
| alias | string? | max:100 |
| documento | string? | max:50 |
| telefono | string? | max:30 |
| email | string? | email, max:255 |
| notas | string? | — |
| activo | bool | — |

**GET /clientes?q=** — búsqueda por nombre/alias (paginated 50).  
**GET /clientes/{id}** — eager carga `cuentas.banco`, `cuentas.moneda`.  
**GET /clientes/{id}/cuentas** — array de cuentas del cliente.

### Categorías de Gasto `{categoria_gasto}`

| Campo | Tipo | Reglas |
|-------|------|--------|
| nombre | string | required, max:100, unique |
| titular_id | int? | exists:titulares |
| activa | bool | — |

Eager carga `titular`.

---

## Operaciones

### `GET /api/v1/operaciones`
`auth:sanctum` | `viewAny` (admin | operador | contador | lectura)

**Query params:** `per_page` (max 100), `fecha_desde`, `fecha_hasta`, `tipo_codigo`, `cliente_id`, `operador_id`, `estatus`, `cuenta_id`

Eager: `tipoOperacion`, `cliente`, `operador`, `movimientos.moneda`.

### `POST /api/v1/operaciones`
`auth:sanctum` | `create` (admin | operador)

```json
{
  "fecha":               "required|date",
  "tipo_codigo":         "required|exists:tipos_operacion,codigo",
  "cliente_id":          "int?",
  "cliente_emisor_id":   "int?|required_if:tipo_codigo,intermediada",
  "cliente_receptor_id": "int?|required_if:tipo_codigo,intermediada",
  "categoria_gasto_id":  "int?|required_if:tipo_codigo,gasto",
  "operador_id":         "required|exists:users",
  "tasa_aplicada":       "numeric?|min:0",
  "tasa_compra":         "numeric?|required_if:tipo_codigo,intermediada",
  "tasa_venta":          "numeric?|required_if:tipo_codigo,intermediada",
  "genera_comision":     "bool?",
  "monto_comision":      "numeric?|min:0",
  "tipo_comision":       "string?|in:pago_movil,otros_bancos,mismo_banco,manual",
  "tasa_mercado_snapshot": "numeric?",
  "fuente_tasa_mercado": "string?|max:30",
  "referencia":          "string?|max:100",
  "descripcion":         "string?",
  "origen":              "string?|in:manual,importado,ajuste_apertura",
  "origen_referencia":   "string?|unique:operaciones",
  "movimientos": [
    {
      "cuenta_id":  "required|exists:cuentas",
      "monto":      "required|numeric|not_in:0",
      "tasa_a_usd": "numeric?|gt:0"
    }
  ]
}
```

**Validación extra:** intermediada requiere `tasa_venta > tasa_compra` y emisor ≠ receptor.  
**movimientos:** min 2 (min 4 para intermediada).

### `GET /api/v1/operaciones/{operacion}`
`auth:sanctum` | `view`

Eager carga: `movimientos.cuenta.titular`, `movimientos.moneda`, `tipoOperacion`, `cliente`, `clienteEmisor`, `clienteReceptor`, `categoriaGasto`, `operador`, `verificadoPor`, `pagador`.

### `PUT|PATCH /api/v1/operaciones/{operacion}`
`auth:sanctum` | owner | admin | super_admin

Mismos campos que store, todos `sometimes`. Requiere `motivo_edicion: required|string|min:10|max:500`.

No permite editar si está verificado (excepto super_admin) ni cancelado.

### `PATCH /api/v1/operaciones/{operacion}/verificar`
`auth:sanctum` | `role:admin|contador`

Sin body. Cambia estatus a `verificado`.

### `DELETE /api/v1/operaciones/{operacion}`
Siempre `405` — las operaciones no se eliminan.

---

### Response `OperacionResource`

```json
{
  "id": "int",
  "fecha": "2026-07-07",
  "estatus": "borrador|verificado",
  "origen": "manual|null",
  "origen_referencia": "string|null",
  "referencia": "string|null",
  "descripcion": "string|null",
  "tasa_aplicada": "700.00000000",
  "tasa_compra": "800.00000000",
  "tasa_venta": "810.00000000",
  "tasa_mercado_snapshot": "700.00000000|null",
  "fuente_tasa_mercado": "bcv|null",
  "tasa_sugerida": "700.00000000",
  "sin_tasa_referencia": false,
  "ganancia": {
    "bruta_usd": "0.0000",
    "bruta_ves": "0.00",
    "real_usd": "0.0000|null",
    "real_ves": "0.00|null",
    "neta_usd": "0.0000",
    "neta_ves": "0.00"
  },
  "comisiones_total": { "usd": "0.0000", "ves": "0.00" },
  "genera_comision": false,
  "monto_comision": "0.0000",
  "tipo_comision": null,
  "verificado_at": null,
  "estado_pool": "pendiente|asignada|pagada|cancelada",
  "pagador": { "id": 1, "name": "Admin" }|null,
  "tipo_operacion": { "id": 1, "codigo": "compra", "nombre": "..." },
  "cliente": { "id": 1, "nombre": "...", "alias": "..." }|null,
  "cliente_emisor": { ... }|null,
  "cliente_receptor": { ... }|null,
  "categoria_gasto": { "id": 1, "nombre": "..." }|null,
  "operador": { "id": 1, "name": "..." },
  "verificado_por": { "id": 1, "name": "..." }|null,
  "movimientos": [
    {
      "id": 1,
      "monto": "-7000.0000",
      "tasa_a_usd": "0.00142857",
      "monto_usd_equivalente": "-10.0000",
      "orden": 1,
      "cuenta": {
        "id": 1, "alias": "...", "tipo": "...",
        "numero_cuenta": "...",
        "banco": { "id": 1, "nombre": "..." }|null,
        "titular": { "id": 1, "nombre": "..." }|null,
        "cliente": { "id": 1, "nombre": "..." }|null
      },
      "moneda": { "id": 1, "codigo": "USD", "simbolo": "$" }
    }
  ],
  "created_at": "2026-07-07T12:00:00Z",
  "updated_at": "2026-07-07T12:00:00Z"
}
```

---

## Comisiones de Operación

### `GET /api/v1/operaciones/{operacion}/comisiones`
`role:admin|super_admin|contador`

Lista comisiones aplicadas a una operación. Incluye moneda, origen (morph), movimiento y editada_por.

### `PATCH /api/v1/operaciones/{operacion}/comisiones/{comision}`
`role:admin|super_admin`

```json
{
  "razon_edicion":         "required|string|min:10|max:500",
  "monto":                 "numeric?|gt:0",
  "monto_usd_equivalente": "numeric?|gte:0",
  "descripcion":           "string?|max:200"
}
```

---

## Pool de Pagadores

`role:pagador|admin|super_admin`

| Método | Ruta | Acción |
|--------|------|--------|
| GET | `/pool` | Órdenes pendientes (más viejas primero) |
| GET | `/pool/mis-ordenes` | Órdenes asignadas al usuario autenticado |
| POST | `/pool/{operacion}/tomar` | Asigna la operación al pagador |
| POST | `/pool/{operacion}/soltar` | Libera la operación |
| POST | `/pool/{operacion}/pagar` | Marca como pagada |
| POST | `/pool/{operacion}/cancelar` | Cancela (requiere `motivo_cancelacion`) |

`role:admin|super_admin`

| Método | Ruta | Acción |
|--------|------|--------|
| POST | `/pool/{operacion}/cancelar` | Cancela cualquier operación |

Todas devuelven `OperacionResource`.

---

## Gastos

| Método | Ruta | Rol |
|--------|------|-----|
| GET | `/gastos` | viewAny |
| POST | `/gastos` | create |
| GET | `/gastos/{operacion}` | view |

Filtran automáticamente por `tipo_operacion.codigo = 'gasto'`.

### `POST /gastos`
```json
{
  "fecha":              "required|date",
  "categoria_gasto_id": "required|exists:categorias_gasto",
  "operador_id":        "required|exists:users",
  "referencia":         "string?|max:100",
  "descripcion":        "string?",
  "movimientos": [
    {
      "cuenta_id":  "required|exists:cuentas",
      "monto":      "required|numeric|not_in:0",
      "tasa_a_usd": "required|numeric|gt:0"
    }
  ]
}
```

Devuelve `OperacionResource`.

---

## Tasas de Mercado

### `GET /api/v1/tasas/actuales`
```json
{
  "tasas": {
    "bcv":            { "fuente": "bcv", "valor": 685.94, "capturado_en": "..." },
    "paralelo":       { ... },
    "binance_p2p_buy":  { "fuente": "binance_p2p_buy", "valor": ..., "mediana": ..., "muestras": 5 },
    "binance_p2p_sell": { ... }
  },
  "spreads": {
    "usdt_sell_vs_bcv":   5.2,
    "usdt_buy_vs_bcv":    4.8,
    "usdt_sell_vs_buy":   0.4
  }
}
```

### `GET /api/v1/tasas/historico`
**Query:** `?fuente=bcv&desde=2026-07-01&hasta=2026-07-08`

Paginated (50). Cada item: `{ id, fuente, par, valor, capturado_en }`.

---

## Dashboard

### `GET /api/v1/dashboard/general`
Tasas vigentes, referencia de mercado, alertas.

### `GET /api/v1/dashboard/tasas-referencia`
```json
{ "bcv": { "tasa": 685.94, "capturado_en": "..." }, "binance_p2p": { ... } }
```

### `GET /api/v1/dashboard/resumen`
**Query:** `?fecha_desde=&fecha_hasta=&moneda=&operador_id=`

```json
{
  "periodo": { "desde": "...", "hasta": "..." },
  "operaciones": { "total": 10, "compras": 5, "ventas": 3, "intermediadas": 2 },
  "volumenes": [ { "moneda": "USD", "comprado": 5000, "vendido": 3000 } ],
  "ganancias": { "bruta_usd": 150.00, "neta_usd": 120.00 },
  "por_operador": [ { "operador": "Admin", "total_operaciones": 10, "volumen_usd": 8000 } ],
  "efectivo_pendiente": { "count": 2, "monto_usd": 500 }
}
```

---

## Configuración

### Tasas Diarias

`role:admin|super_admin` para POST. Lectura para todos.

| Método | Ruta |
|--------|------|
| GET | `/configuracion/tasas-vigentes` |
| GET | `/configuracion/tasas-diarias` |
| GET | `/configuracion/tasas-diarias/historial/{base}/{cotizada}` |
| POST | `/configuracion/tasas-diarias` |

```json
// POST body
{
  "fecha":              "required|date",
  "moneda_base_id":     "required|exists:monedas",
  "moneda_cotizada_id": "required|exists:monedas|different:moneda_base_id",
  "tasa_compra":        "required|numeric|gt:0",
  "tasa_compra_minima": "numeric?|gt:0",
  "tasa_venta":         "required|numeric|gt:0",
  "tasa_venta_minima":  "numeric?|gt:0",
  "notas":              "string?|max:500"
}
```

Si `tasa_venta < tasa_compra` → `notas` requerido (min 10 chars).

### Comisiones (Cuenta / Operador / Método Pago)

Todos son `apiResource` bajo `/configuracion/`, solo `role:admin|super_admin`.

| Recurso | Parámetro |
|---------|-----------|
| `/configuracion/comisiones-cuenta` | `{comisionCuenta}` |
| `/configuracion/comisiones-operador` | `{comisionOperador}` |
| `/configuracion/comisiones-metodo-pago` | `{comisionMetodoPago}` |

Campos comunes: `descripcion`, `tipo_calculo` (porcentaje|monto_fijo), `valor`, `moneda_id`, `vigente_desde`, `vigente_hasta`, `activa`.

DELETE soft-deactiva: `activa=false`, `vigente_hasta=today`.

---

## Reportes de Comisiones

`role:admin|super_admin|contador`

| Método | Ruta |
|--------|------|
| GET | `/reportes/comisiones-operadores` |
| POST | `/reportes/comisiones-operadores/exportar` |
| GET | `/reportes/comisiones-operadores/historico` |

### `POST /exportar`
```json
{ "desde": "required|date", "hasta": "required|date", "formato": "required|in:excel,pdf" }
```
→ `{ "data": { "path": "...", "url": "...", "formato": "...", "generado_en": "..." } }`

---

## Usuarios

`role:admin|super_admin`

`apiResource` en `/usuarios`.

```json
// POST
{ "name": "required|max:255", "email": "required|email|unique", "password": "required|min:8", "rol": "required|in:super_admin,admin,operador,contador,lectura", "titular_id": "int?|exists:titulares", "activo": true }
```

DELETE: desactiva (`activo=false`), no borra.

---

## Bitácora

### `GET /api/v1/admin/bitacora`
`role:super_admin`

**Query:** `?modelo=&user_id=&desde=&hasta=`

Paginated (50) de `Activity` log. `created_at DESC`.
