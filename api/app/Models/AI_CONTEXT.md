# Models — AI Context

## Resumen

16 modelos Eloquent. Todos en `App\Models` namespace. El sistema es una plataforma de gestión de operaciones financieras (compra/venta de divisas) con control de comisiones, tasas de cambio, cuentas bancarias, clientes, titulares y usuarios.

Convenciones generales:
- Los `$casts` de tipo `decimal:X` almacenan valores como `string` (VARCHAR / DECIMAL en DB) para evitar pérdida de precisión.
- `HasFactory` está presente en todos los modelos.
- `SoftDeletes` se usa en Cliente, Cuenta, Operacion, Titular y User.
- `LogsActivity` (Spatie Activitylog) se usa en los 4 modelos de comisiones y en TasaDiaria.
- Timestamps `created_at`/`updated_at` están habilitados por defecto en todos los modelos (no se declara `$timestamps = false` en ninguno).

---

## Modelos

---

### `Banco`

- **Tabla**: `bancos`
- **Traits**: `HasFactory`
- **Fillable**:
  - `nombre` — Nombre de la entidad financiera (ej. "Banco Mercantil", "Banco de Venezuela").
  - `codigo` — Código bancario o SWIFT (opcional).
  - `pais` — País de origen del banco (opcional).
  - `activo` — Si el banco está habilitado para su uso en el sistema.
- **Casts**:
  - `activo` → `boolean`
- **Relaciones**:
  - `cuentas()` → HasMany `App\Models\Cuenta` — Cuentas bancarias asociadas a este banco.
- **Scopes**: ninguno

---

### `CategoriaGasto`

- **Tabla**: `categorias_gasto`
- **Traits**: `HasFactory`
- **Fillable**:
  - `nombre` — Nombre de la categoría (ej. "Alquiler", "Servicios", "Comisiones").
  - `titular_id` — FK al titular que creó/posee esta categoría.
  - `activa` — Si la categoría está disponible para seleccionar en operaciones.
- **Casts**:
  - `activa` → `boolean`
- **Relaciones**:
  - `titular()` → BelongsTo `App\Models\Titular` — Titular propietario de la categoría.
- **Scopes**: ninguno

---

### `Cliente`

- **Tabla**: `clientes`
- **Traits**: `HasFactory`, `SoftDeletes`
- **Fillable**:
  - `nombre` — Nombre o razón social del cliente.
  - `alias` — Nombre corto o apodo para identificar rápidamente al cliente.
  - `documento` — Número de documento (Cédula, RIF, Pasaporte).
  - `telefono` — Número de teléfono de contacto.
  - `email` — Correo electrónico de contacto.
  - `notas` — Observaciones internas sobre el cliente.
  - `saldo_cache_usd` — Saldo en USD cacheadoc calculado (string para precisión decimal).
  - `saldo_cache_at` — Timestamp de la última vez que se calculó `saldo_cache_usd`.
  - `activo` — Si el cliente está habilitado para operar.
- **Casts**:
  - `activo` → `boolean`
  - `saldo_cache_usd` → `string` (se almacena como string para mantener precisión decimal).
  - `saldo_cache_at` → `datetime`
- **Relaciones**:
  - `cuentas()` → HasMany `App\Models\Cuenta` — Cuentas asociadas al cliente.
- **Scopes**: ninguno
- **Notas**: soft-delete implementado, `deleted_at` nullable. `saldo_cache_*` son campos de caché para evitar recalcular saldos en cada consulta.

---

### `ComisionCuenta`

- **Tabla**: `comisiones_cuenta`
- **Traits**: `HasFactory`, `LogsActivity`
- **Fillable**:
  - `cuenta_id` — FK a cuenta específica (opcional si se define `banco_id`).
  - `banco_id` — FK a banco (opcional si se define `cuenta_id`).
  - `descripcion` — Descripción textual de la comisión.
  - `tipo_calculo` — Tipo de cálculo: "porcentual", "fijo", u otro.
  - `valor` — Valor numérico de la comisión (porcentaje o monto fijo).
  - `moneda_id` — FK a la moneda en que se expresa la comisión.
  - `aplica_a` — Ámbito de aplicación (ej. "compra", "venta", "ambos").
  - `vigente_desde` — Fecha de inicio de vigencia.
  - `vigente_hasta` — Fecha de fin de vigencia (null = vigencia indefinida).
  - `activa` — Si la comisión está activa.
- **Casts**:
  - `valor` → `decimal:8`
  - `vigente_desde` → `date`
  - `vigente_hasta` → `date`
  - `activa` → `boolean`
- **Relaciones**:
  - `cuenta()` → BelongsTo `App\Models\Cuenta` — Cuenta a la que aplica la comisión.
  - `banco()` → BelongsTo `App\Models\Banco` — Banco al que aplica la comisión.
  - `moneda()` → BelongsTo `App\Models\Moneda` — Moneda en que se expresa el valor.
- **Scopes**:
  - `scopeVigentes(Builder $query, Carbon|string $fecha)` — Filtra comisiones vigentes en una fecha dada. Evalúa `vigente_desde <= fecha` y (`vigente_hasta IS NULL` o `vigente_hasta >= fecha`).
- **Eventos / Observers**:
  - Evento `saving` (vía `booted`): valida que al menos `cuenta_id` o `banco_id` esté presente; lanza `InvalidArgumentException` si ambos son null.
- **Notas**: Activitylog registra cambios en `valor`, `tipo_calculo`, `activa`, `vigente_desde`, `vigente_hasta`.

---

### `ComisionMetodoPago`

- **Tabla**: `comisiones_metodo_pago`
- **Traits**: `HasFactory`, `LogsActivity`
- **Fillable**:
  - `nombre_metodo` — Nombre del método de pago (ej. "Transferencia", "Efectivo", "Zelle", "PayPal").
  - `cuenta_id` — FK a la cuenta asociada.
  - `descripcion` — Descripción textual.
  - `tipo_calculo` — "porcentual" o "fijo".
  - `valor` — Valor de la comisión.
  - `moneda_id` — FK a la moneda.
  - `vigente_desde` — Fecha de inicio de vigencia.
  - `vigente_hasta` — Fecha de fin de vigencia (null = indefinido).
  - `activa` — Si está activa.
- **Casts**:
  - `valor` → `decimal:8`
  - `vigente_desde` → `date`
  - `vigente_hasta` → `date`
  - `activa` → `boolean`
- **Relaciones**:
  - `cuenta()` → BelongsTo `App\Models\Cuenta` — Cuenta asociada.
  - `moneda()` → BelongsTo `App\Models\Moneda` — Moneda del valor.
- **Scopes**:
  - `scopeVigentes(Builder $query, Carbon|string $fecha)` — Misma lógica que ComisionCuenta: filtra por rango de fechas de vigencia.
- **Notas**: Activitylog registra cambios en `valor`, `tipo_calculo`, `activa`, `vigente_desde`, `vigente_hasta`.

---

### `ComisionOperacion`

- **Tabla**: `comisiones_operacion`
- **Traits**: `HasFactory`, `LogsActivity`
- **Fillable**:
  - `operacion_id` — FK a la operación a la que se aplica esta comisión.
  - `tipo` — Tipo de comisión (ej. "por_operador", "por_metodo_pago", "por_cuenta").
  - `origen_type` — Clase del modelo morph que originó la comisión (polimórfico).
  - `origen_id` — ID del registro morph de origen.
  - `descripcion` — Descripción textual.
  - `monto` — Monto calculado de la comisión.
  - `moneda_id` — FK a la moneda del monto.
  - `monto_usd_equivalente` — Monto convertido a USD (para uniformidad de reportes).
  - `movimiento_id` — FK al movimiento que generó esta comisión (opcional).
  - `editada_por_id` — FK al usuario que editó manualmente la comisión (opcional).
  - `editada_at` — Timestamp de la edición manual.
  - `razon_edicion` — Motivo por el cual se editó manualmente la comisión.
- **Casts**:
  - `monto` → `decimal:4`
  - `monto_usd_equivalente` → `decimal:4`
  - `editada_at` → `datetime`
- **Relaciones**:
  - `operacion()` → BelongsTo `App\Models\Operacion` — Operación a la que pertenece.
  - `origen()` → MorphTo `Illuminate\Database\Eloquent\Model` — Origen polimórfico (ComisionCuenta, ComisionMetodoPago, ComisionOperador, etc.).
  - `moneda()` → BelongsTo `App\Models\Moneda` — Moneda del monto.
  - `movimiento()` → BelongsTo `App\Models\Movimiento` — Movimiento asociado (opcional).
  - `editadaPor()` → BelongsTo `App\Models\User` — Usuario que editó la comisión.
- **Scopes**: ninguno
- **Notas**: Activitylog registra cambios en `monto`, `monto_usd_equivalente`, `descripcion`, `razon_edicion`, `editada_por_id`.

---

### `ComisionOperador`

- **Tabla**: `comisiones_operador`
- **Traits**: `HasFactory`, `LogsActivity`
- **Fillable**:
  - `titular_id` — FK al titular (operador) al que aplica esta comisión.
  - `tipo_operacion_id` — FK al tipo de operación (compra, venta, etc.).
  - `descripcion` — Descripción textual.
  - `tipo_calculo` — "porcentual" o "fijo".
  - `valor` — Valor de la comisión.
  - `moneda_id` — FK a la moneda.
  - `base_calculo` — Base sobre la que se calcula (ej. "monto_operacion", "ganancia_bruta").
  - `vigente_desde` — Fecha de inicio de vigencia.
  - `vigente_hasta` — Fecha de fin de vigencia (null = indefinido).
  - `activa` — Si está activa.
- **Casts**:
  - `valor` → `decimal:8`
  - `vigente_desde` → `date`
  - `vigente_hasta` → `date`
  - `activa` → `boolean`
- **Relaciones**:
  - `titular()` → BelongsTo `App\Models\Titular` — Titular/operador al que pertenece.
  - `tipoOperacion()` → BelongsTo `App\Models\TipoOperacion` — Tipo de operación.
  - `moneda()` → BelongsTo `App\Models\Moneda` — Moneda del valor.
- **Scopes**:
  - `scopeVigentes(Builder $query, Carbon|string $fecha)` — Misma lógica de vigencia por rango de fechas.
- **Notas**: Activitylog registra cambios en `valor`, `tipo_calculo`, `base_calculo`, `activa`, `vigente_desde`, `vigente_hasta`.

---

### `Cuenta`

- **Tabla**: `cuentas`
- **Traits**: `HasFactory`, `SoftDeletes`
- **Fillable**:
  - `titular_id` — FK al titular propietario (opcional si tiene `cliente_id`).
  - `cliente_id` — FK al cliente propietario (opcional si tiene `titular_id`).
  - `banco_id` — FK al banco asociado (opcional, ej. cuentas de efectivo no tienen banco).
  - `moneda_id` — FK a la moneda de la cuenta.
  - `alias` — Nombre interno para identificar la cuenta.
  - `tipo` — Tipo de cuenta: banco, efectivo, cripto, etc.
  - `numero_cuenta` — Número de cuenta bancaria (opcional).
  - `saldo_cache` — Saldo cacheadoc calculado (string para precisión).
  - `saldo_cache_at` — Timestamp del último cálculo de saldo.
  - `activa` — Si la cuenta está habilitada.
  - `notas` — Notas internas sobre la cuenta.
- **Casts**:
  - `activa` → `boolean`
  - `saldo_cache` → `string`
  - `saldo_cache_at` → `datetime`
- **Relaciones**:
  - `titular()` → BelongsTo `App\Models\Titular` — Titular dueño de la cuenta.
  - `cliente()` → BelongsTo `App\Models\Cliente` — Cliente dueño de la cuenta.
  - `banco()` → BelongsTo `App\Models\Banco` — Banco de la cuenta.
  - `moneda()` → BelongsTo `App\Models\Moneda` — Moneda de la cuenta.
- **Scopes**: ninguno
- **Eventos / Observers**:
  - Evento `saving` (vía `boot`): valida que la cuenta pertenezca **exclusivamente** a un titular O a un cliente, no ambos y no ninguno. Lanza `ValidationException` si la validación falla.
- **Notas**: soft-delete implementado. Es la entidad central que conecta titulares/clientes con bancos y monedas.

---

### `Moneda`

- **Tabla**: `monedas`
- **Traits**: `HasFactory`
- **Fillable**:
  - `codigo` — Código ISO de la moneda (ej. "USD", "VES", "EUR", "BTC").
  - `nombre` — Nombre completo (ej. "Dólar estadounidense", "Bolívar soberano").
  - `simbolo` — Símbolo monetario (ej. "$", "Bs.", "€").
  - `es_fiat` — Si es moneda fiduciaria (true) o no.
  - `es_cripto` — Si es criptomoneda (true).
  - `decimales` — Número de decimales soportados (ej. 2 para USD/VES, 8 para BTC).
  - `activa` — Si la moneda está disponible en el sistema.
- **Casts**:
  - `es_fiat` → `boolean`
  - `es_cripto` → `boolean`
  - `activa` → `boolean`
  - `decimales` → `integer`
- **Relaciones**:
  - `cuentas()` → HasMany `App\Models\Cuenta` — Cuentas denominadas en esta moneda.
- **Scopes**: ninguno
- **Notas**: Una moneda puede ser fiat y cripto simultáneamente (aunque no es lo habitual).

---

### `Movimiento`

- **Tabla**: `movimientos`
- **Traits**: `HasFactory`
- **Fillable**:
  - `operacion_id` — FK a la operación a la que pertenece el movimiento.
  - `cuenta_id` — FK a la cuenta afectada (origen o destino).
  - `moneda_id` — FK a la moneda del movimiento.
  - `monto` — Monto del movimiento en la moneda de la cuenta.
  - `tasa_a_usd` — Tasa de cambio usada para convertir a USD (en el momento del movimiento).
  - `monto_usd_equivalente` — Monto convertido a USD.
  - `orden` — Orden secuencial del movimiento dentro de la operación (para preservar la secuencia lógica).
- **Casts**:
  - `monto` → `decimal:4`
  - `tasa_a_usd` → `decimal:8`
  - `monto_usd_equivalente` → `decimal:4`
- **Relaciones**:
  - `operacion()` → BelongsTo `App\Models\Operacion` — Operación contenedora.
  - `cuenta()` → BelongsTo `App\Models\Cuenta` — Cuenta debitada/acreditada.
  - `moneda()` → BelongsTo `App\Models\Moneda` — Moneda del monto.
- **Scopes**: ninguno
- **Notas**: No tiene timestamps personalizados; usa los defaults `created_at`/`updated_at`. Cada operación puede tener múltiples movimientos (ej. débito de una cuenta y crédito a otra).

---

### `Operacion`

- **Tabla**: `operaciones`
- **Traits**: `HasFactory`, `SoftDeletes`
- **Fillable**:
  - `fecha` — Fecha de la operación.
  - `tipo_operacion_id` — FK al tipo de operación (compra, venta, gasto, etc.).
  - `cliente_id` — FK al cliente principal de la operación.
  - `cliente_emisor_id` — FK al cliente que envía fondos (opcional, para operaciones con 2 clientes).
  - `cliente_receptor_id` — FK al cliente que recibe fondos (opcional).
  - `categoria_gasto_id` — FK a categoría de gasto (solo para operaciones tipo gasto).
  - `operador_id` — FK al usuario operador que realizó la operación.
  - `tasa_aplicada` — Tasa de cambio efectivamente aplicada en la operación.
  - `tasa_compra` — Tasa de compra usada como referencia (si aplica).
  - `tasa_venta` — Tasa de venta usada como referencia (si aplica).
  - `genera_comision` — Indica si la operación genera comisiones.
  - `monto_comision` — Monto total de comisiones calculado.
  - `tipo_comision` — Tipo de comisión aplicada.
  - `tasa_mercado_snapshot` — Tasa de mercado capturada al momento de la operación.
  - `fuente_tasa_mercado` — Fuente de la tasa de mercado (ej. "BCV", "CoinGecko", "manual").
  - `tasa_sugerida` — Tasa sugerida por el sistema para la operación.
  - `tasa_diaria_id` — FK a la tasa diaria usada como referencia.
  - `sin_tasa_referencia` — Si la operación se realizó sin tasa de referencia.
  - `ganancia_bruta_usd` — Ganancia bruta en USD.
  - `ganancia_real_usd` — Ganancia real en USD (después de costos directos).
  - `ganancia_bruta_ves` — Ganancia bruta en VES (bolívares).
  - `ganancia_real_ves` — Ganancia real en VES.
  - `total_comisiones_usd` — Suma de comisiones en USD.
  - `total_comisiones_ves` — Suma de comisiones en VES.
  - `ganancia_neta_usd` — Ganancia neta final en USD.
  - `ganancia_neta_ves` — Ganancia neta final en VES.
  - `referencia` — Número de referencia o identificador externo.
  - `descripcion` — Descripción textual de la operación.
  - `estatus` — Estatus de la operación (ej. "pendiente", "completada", "cancelada").
  - `verificado_at` — Timestamp de verificación.
  - `verificado_por_id` — FK al usuario que verificó la operación.
  - `origen` — Origen de la operación (ej. "web", "API", "manual").
  - `origen_referencia` — ID de referencia externa según el origen.
  - `estado_pool` — Estado en el pool de pagos ("pendiente", "asignada", "pagada", "cancelada").
  - `pagador_id` — FK al usuario responsable de pagar la operación.
  - `asignada_at` — Timestamp de asignación al pagador.
  - `pagada_at` — Timestamp de pago efectivo.
  - `cancelada_at` — Timestamp de cancelación.
  - `motivo_cancelacion` — Razón de la cancelación.
  - `sla_notificado_en` — Timestamp de cuándo se notificó la alarma de SLA (null = no notificada).
- **Casts**:
  - `fecha` → `date`
  - `sla_notificado_en` → `datetime`
  - `verificado_at` → `datetime`
  - `asignada_at` → `datetime`
  - `pagada_at` → `datetime`
  - `cancelada_at` → `datetime`
  - `genera_comision` → `boolean`
  - `monto_comision` → `decimal:4`
  - `sin_tasa_referencia` → `boolean`
  - `ganancia_bruta_usd` → `decimal:4`
  - `ganancia_real_usd` → `decimal:4`
  - `ganancia_bruta_ves` → `decimal:2`
  - `ganancia_real_ves` → `decimal:2`
  - `total_comisiones_usd` → `decimal:4`
  - `total_comisiones_ves` → `decimal:2`
  - `ganancia_neta_usd` → `decimal:4`
  - `ganancia_neta_ves` → `decimal:2`
  - `tasa_aplicada` → `decimal:8`
  - `tasa_sugerida` → `decimal:8`
  - `tasa_mercado_snapshot` → `decimal:8`
  - `tasa_compra` → `decimal:8`
  - `tasa_venta` → `decimal:8`
- **Relaciones**:
  - `movimientos()` → HasMany `App\Models\Movimiento` (ordenado por `orden`) — Movimientos de fondos de la operación.
  - `tipoOperacion()` → BelongsTo `App\Models\TipoOperacion` — Tipo de operación.
  - `cliente()` → BelongsTo `App\Models\Cliente` — Cliente principal.
  - `categoriaGasto()` → BelongsTo `App\Models\CategoriaGasto` — Categoría de gasto (si aplica).
  - `operador()` → BelongsTo `App\Models\User` (FK: `operador_id`) — Usuario que realizó la operación.
  - `verificadoPor()` → BelongsTo `App\Models\User` (FK: `verificado_por_id`) — Usuario que verificó.
  - `pagador()` → BelongsTo `App\Models\User` (FK: `pagador_id`) — Usuario asignado a pagar.
  - `tasaDiaria()` → BelongsTo `App\Models\TasaDiaria` — Tasa diaria de referencia.
  - `comisiones()` → HasMany `App\Models\ComisionOperacion` — Comisiones aplicadas.
  - `clienteEmisor()` → BelongsTo `App\Models\Cliente` (FK: `cliente_emisor_id`) — Cliente emisor.
  - `clienteReceptor()` → BelongsTo `App\Models\Cliente` (FK: `cliente_receptor_id`) — Cliente receptor.
- **Scopes**:
  - `scopePendientes(Builder $query)` — Filtra operaciones con `estado_pool = 'pendiente'`.
  - `scopeAsignadasA(Builder $query, int $userId)` — Filtra operaciones asignadas a un usuario específico (`pagador_id = userId` y `estado_pool = 'asignada'`).
- **Notas**: Es el modelo más grande y complejo del sistema. Contiene campos de ganancias en USD y VES, control de pool de pagos, verificación y cancelación. `sla_notificado_en` evita que la alarma de SLA se repita cada minuto. soft-delete implementado.

---

### `TasaDiaria`

- **Tabla**: `tasas_diarias`
- **Traits**: `HasFactory`, `LogsActivity`
- **Fillable**:
  - `fecha` — Fecha a la que corresponde la tasa.
  - `moneda_base_id` — FK a la moneda base (ej. USD).
  - `moneda_cotizada_id` — FK a la moneda cotizada (ej. VES).
  - `tasa_compra` — Tasa a la que la casa compra la moneda base (precio de compra).
  - `tasa_compra_minima` — Tasa mínima aceptable de compra (límite inferior para alertas).
  - `tasa_venta` — Tasa a la que la casa vende la moneda base (precio de venta).
  - `tasa_venta_minima` — Tasa mínima aceptable de venta (límite inferior para alertas).
  - `definida_por_id` — FK al usuario que definió/cargó la tasa.
  - `notas` — Notas internas sobre la tasa.
  - `vigente_desde` — Inicio de vigencia (datetime).
  - `vigente_hasta` — Fin de vigencia (datetime, null = indefinido).
- **Casts**:
  - `fecha` → `date`
  - `tasa_compra` → `decimal:8`
  - `tasa_compra_minima` → `decimal:8`
  - `tasa_venta` → `decimal:8`
  - `tasa_venta_minima` → `decimal:8`
  - `vigente_desde` → `datetime`
  - `vigente_hasta` → `datetime`
- **Relaciones**:
  - `monedaBase()` → BelongsTo `App\Models\Moneda` (FK: `moneda_base_id`) — Moneda base del par.
  - `monedaCotizada()` → BelongsTo `App\Models\Moneda` (FK: `moneda_cotizada_id`) — Moneda cotizada del par.
  - `definidaPor()` → BelongsTo `App\Models\User` (FK: `definida_por_id`) — Usuario que definió la tasa.
  - `operaciones()` → HasMany `App\Models\Operacion` — Operaciones que usaron esta tasa como referencia.
- **Scopes**:
  - `scopeVigentes(Builder $query, ?Carbon $momento = null)` — Filtra tasas vigentes en un momento dado. Evalúa `vigente_desde <= $momento` y (`vigente_hasta IS NULL` o `vigente_hasta > $momento`). Si no se pasa momento, usa `now()`.
- **Métodos adicionales**:
  - `esDesfavorableParaLaCasa(float $tasaEfectiva, string $direccion): bool` — Evalúa si una tasa efectiva es desfavorable respecto al mínimo configurado. `direccion` = `'compra'` (desfavorable si tasa > tasa_compra_minima) o `'venta'` (desfavorable si tasa < tasa_venta_minima). Si el mínimo es null, retorna false.
- **Notas**: Activitylog registra cambios en `tasa_compra`, `tasa_compra_minima`, `tasa_venta`, `tasa_venta_minima`, `vigente_desde`, `vigente_hasta`.

---

### `TasaMercado`

- **Tabla**: `tasas_mercado`
- **Traits**: `HasFactory`
- **Fillable**:
  - `fuente` — Nombre de la fuente externa (ej. "BCV", "CoinGecko", "Binance", "Yadio").
  - `moneda_base_id` — FK a la moneda base.
  - `moneda_cotizada_id` — FK a la moneda cotizada.
  - `valor` — Valor de la tasa obtenido de la fuente.
  - `capturado_en` — Timestamp de cuándo se capturó el dato de la fuente.
  - `payload_original` — JSON completo devuelto por la fuente API (para trazabilidad).
- **Casts**:
  - `valor` → `decimal:8`
  - `capturado_en` → `datetime`
  - `payload_original` → `array` (se serializa/deserializa automáticamente como JSON).
- **Relaciones**:
  - `monedaBase()` → BelongsTo `App\Models\Moneda` (FK: `moneda_base_id`) — Moneda base del par.
  - `monedaCotizada()` → BelongsTo `App\Models\Moneda` (FK: `moneda_cotizada_id`) — Moneda cotizada del par.
- **Scopes**: ninguno
- **Notas**: Almacena datos crudos de fuentes externas. No tiene SoftDeletes (los datos históricos de mercado no se eliminan).

---

### `TipoOperacion`

- **Tabla**: `tipos_operacion`
- **Traits**: `HasFactory`
- **Fillable**:
  - `codigo` — Código único del tipo (ej. "COMPRA", "VENTA", "GASTO", "TRANSFERENCIA").
  - `nombre` — Nombre descriptivo (ej. "Compra de divisas", "Venta de divisas").
  - `afecta_cliente` — Si la operación afecta el saldo/estado del cliente.
  - `afecta_fifo` — Si la operación afecta el cálculo FIFO de inventario de divisas.
  - `genera_ganancia` — Si la operación genera ganancia/pérdida para la casa.
  - `activo` — Si el tipo está disponible para nuevas operaciones.
- **Casts**:
  - `afecta_cliente` → `boolean`
  - `afecta_fifo` → `boolean`
  - `genera_ganancia` → `boolean`
  - `activo` → `boolean`
- **Relaciones**: ninguna (es modelo de referencia/catálogo).
- **Scopes**: ninguno

---

### `Titular`

- **Tabla**: `titulares`
- **Traits**: `HasFactory`, `SoftDeletes`
- **Fillable**:
  - `nombre` — Nombre o razón social del titular (persona natural o jurídica).
  - `alias` — Nombre corto de uso interno.
  - `activo` — Si el titular está habilitado.
- **Casts**:
  - `activo` → `boolean`
- **Relaciones**:
  - `cuentas()` → HasMany `App\Models\Cuenta` — Cuentas propiedad del titular.
  - `users()` → HasMany `App\Models\User` — Usuarios del sistema asociados al titular.
  - `categoriasGasto()` → HasMany `App\Models\CategoriaGasto` — Categorías de gasto creadas por el titular.
- **Scopes**: ninguno
- **Notas**: soft-delete implementado. Un titular es una entidad propietaria de cuentas; puede tener múltiples usuarios del sistema asociados.

---

### `User`

- **Tabla**: `users` (por defecto de Laravel)
- **Extiende**: `Illuminate\Foundation\Auth\User` (Authenticatable)
- **Implementa**: `Illuminate\Contracts\Auth\MustVerifyEmail` — obliga a verificar email antes de login
- **Traits**: `HasApiTokens` (Sanctum), `HasFactory`, `HasRoles` (Spatie Permission), `Notifiable`, `SoftDeletes`
- **Fillable**:
  - `name` — Nombre completo del usuario.
  - `email` — Correo electrónico (usado para login).
  - `password` — Contraseña hasheada.
  - `titular_id` — FK al titular al que pertenece el usuario (opcional).
  - `activo` — Si el usuario puede iniciar sesión.
  - `last_login_at` — Timestamp del último inicio de sesión exitoso.
- **Hidden**:
  - `password` — Oculto en serialización.
  - `remember_token` — Token "recordarme" de sesión.
- **Casts**:
  - `email_verified_at` → `datetime`
  - `password` → `hashed` (se hashea automáticamente al asignar).
  - `activo` → `boolean`
  - `last_login_at` → `datetime`
- **Relaciones**:
  - `titular()` → BelongsTo `App\Models\Titular` — Titular al que pertenece el usuario.
- **Métodos personalizados**:
  - `sendEmailVerificationNotification()` — Envía `VerifyEmailNotification` al usuario. Laravel lo llama automáticamente al crear el usuario si implementa `MustVerifyEmail`.
- **Scopes**: ninguno
- **Notas**: 
  - Implementa `MustVerifyEmail` — el usuario no puede hacer login hasta que `email_verified_at` no sea null.
  - `email_verified_at` se setea cuando el usuario verifica su email vía link en el correo.
  - El email contiene un link al frontend (`/email/verify?email=X&hash=Y`) que llama al POST `/api/v1/auth/verificar-email`.
  - Usa `HasRoles` de Spatie Permission para manejo de roles y permisos.
  - Usa `HasApiTokens` de Sanctum para autenticación por API tokens.
  - Soft-delete implementado.

---

## Resumen de relaciones entre modelos

```
Titular (1) ──< (N) Cuenta
Titular (1) ──< (N) User
Titular (1) ──< (N) CategoriaGasto
Titular (1) ──< (N) ComisionOperador

Cliente (1) ──< (N) Cuenta
Cliente (1) ──< (N) Operacion (cliente_id, cliente_emisor_id, cliente_receptor_id)

Banco (1) ──< (N) Cuenta
Banco (1) ──< (N) ComisionCuenta

Moneda (1) ──< (N) Cuenta
Moneda (1) ──< (N) Movimiento
Moneda (1) ──< (N) ComisionCuenta
Moneda (1) ──< (N) ComisionMetodoPago
Moneda (1) ──< (N) ComisionOperacion
Moneda (1) ──< (N) ComisionOperador
Moneda (1) ──< (N) TasaDiaria (moneda_base_id, moneda_cotizada_id)
Moneda (1) ──< (N) TasaMercado (moneda_base_id, moneda_cotizada_id)

TipoOperacion (1) ──< (N) Operacion
TipoOperacion (1) ──< (N) ComisionOperador

User (1) ──< (N) Operacion (operador_id, verificado_por_id, pagador_id)
User (1) ──< (N) ComisionOperacion (editada_por_id)
User (1) ──< (N) TasaDiaria (definida_por_id)

Operacion (1) ──< (N) Movimiento
Operacion (1) ──< (N) ComisionOperacion

Cuenta (1) ──< (N) Movimiento
Cuenta (1) ──< (N) ComisionCuenta
Cuenta (1) ──< (N) ComisionMetodoPago

Movimiento (1) ──< (N) ComisionOperacion

TasaDiaria (1) ──< (N) Operacion

CategoriaGasto (1) ──< (N) Operacion

ComisionOperacion → MorphTo (origen: ComisionCuenta|ComisionMetodoPago|ComisionOperador)
```

## Resumen de Scopes

| Scope | Modelo | Propósito |
|-------|--------|-----------|
| `vigentes($fecha)` | ComisionCuenta | Comisiones vigentes en una fecha |
| `vigentes($fecha)` | ComisionMetodoPago | Comisiones vigentes en una fecha |
| `vigentes($fecha)` | ComisionOperador | Comisiones vigentes en una fecha |
| `vigentes($momento)` | TasaDiaria | Tasas vigentes en un momento |
| `pendientes()` | Operacion | Operaciones en pool pendiente |
| `asignadasA($userId)` | Operacion | Operaciones asignadas a un pagador |

## Resumen de Activitylog (Spatie)

| Modelo | Campos auditados |
|--------|-----------------|
| ComisionCuenta | `valor`, `tipo_calculo`, `activa`, `vigente_desde`, `vigente_hasta` |
| ComisionMetodoPago | `valor`, `tipo_calculo`, `activa`, `vigente_desde`, `vigente_hasta` |
| ComisionOperacion | `monto`, `monto_usd_equivalente`, `descripcion`, `razon_edicion`, `editada_por_id` |
| ComisionOperador | `valor`, `tipo_calculo`, `base_calculo`, `activa`, `vigente_desde`, `vigente_hasta` |
| TasaDiaria | `tasa_compra`, `tasa_compra_minima`, `tasa_venta`, `tasa_venta_minima`, `vigente_desde`, `vigente_hasta` |

## Resumen de Eventos / Validaciones en boot()

| Modelo | Evento | Validación |
|--------|--------|------------|
| ComisionCuenta | `saving` | Requiere al menos `cuenta_id` o `banco_id` |
| Cuenta | `saving` | Debe pertenecer a titular XOR cliente (no ambos, no ninguno) |
