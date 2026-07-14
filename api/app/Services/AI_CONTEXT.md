# Services — AI Context

---

## `TasasMercadoService`

- **File**: `api/app/Services/Tasas/TasasMercadoService.php`
- **Namespace**: `App\Services\Tasas`
- **Propósito**: Consultar tasas de cambio desde APIs externas (BCV vía dolarapi.com y Binance P2P para USDT/VES).
- **Dependencias**: `Illuminate\Support\Facades\Http`, `Illuminate\Support\Facades\Log`

---

### `obtenerBcv(): ?array`
- **Propósito**: Obtener la tasa oficial del BCV (promedio ponderado).
- **Fuente externa**: `GET https://ve.dolarapi.com/v1/dolares/oficial`
- **Lógica**:
  1. HTTP GET con timeout de 10s y 2 reintentos con 500ms de espera.
  2. `throw()` para lanzar excepción si hay error HTTP.
  3. Extrae `promedio` o `promedioVenta` del JSON como `valor`.
- **Retorna**: `['fuente' => 'bcv', 'par' => 'USD/VES', 'valor' => float, 'payload' => array]`
- **Retorna en error**: `null` (loggea warning)
- **Excepciones capturadas**: `\Throwable` (cualquier error de red/HTTP)

---

### `obtenerParalelo(): ?array`
- **Propósito**: Obtener la tasa del dólar paralelo.
- **Fuente externa**: `GET https://ve.dolarapi.com/v1/dolares/paralelo`
- **Lógica**: Idéntica a `obtenerBcv()` pero cambia el endpoint.
- **Retorna**: `['fuente' => 'paralelo', 'par' => 'USD/VES', 'valor' => float, 'payload' => array]`
- **Retorna en error**: `null`

---

### `obtenerBinanceP2P(string $tradeType = 'BUY', int $top = 10): ?array`
- **Propósito**: Obtener precios de USDT/VES desde Binance P2P.
- **Fuente externa**: `POST https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search`
- **Parámetros**:
  - `$tradeType` (string, default `'BUY'`): `'BUY'` = compradores de USDT, `'SELL'` = vendedores de USDT.
  - `$top` (int, default `10`): cantidad de anuncios a consultar.
- **Lógica**:
  1. HTTP POST con timeout 10s, 2 reintentos, headers JSON + User-Agent.
  2. Body: `{fiat: 'VES', asset: 'USDT', tradeType, page: 1, rows: $top, payTypes: [], publisherType: null}`.
  3. Colecciona precios de `data[*].adv.price`, filtra > 0.
  4. Si la colección está vacía → retorna `null`.
  5. Calcula: promedio (`avg`), mediana, min, max, cantidad de muestras.
  6. Redondea a 8 decimales.
- **Retorna**: `['fuente' => 'binance_p2p_buy'|'binance_p2p_sell', 'par' => 'USDT/VES', 'valor' => float, 'mediana' => float, 'min' => float, 'max' => float, 'muestras' => int, 'payload' => array]`
- **Retorna en error**: `null`

---

## `TasaDiariaService`

- **File**: `api/app/Services/Configuracion/TasaDiariaService.php`
- **Namespace**: `App\Services\Configuracion`
- **Propósito**: Gestionar tasas diarias (TasaDiaria): publicación, consulta de vigente/última, validación contra mínimos, resolución de dirección y de par de monedas desde movimientos.
- **Modelos que usa**: `TasaDiaria`, `Moneda`
- **Excepciones**: `ValidationException` (Laravel), `\InvalidArgumentException` (desde `TasaDiaria::esDesfavorableParaLaCasa`)

---

### `publicar(array $payload, User $admin): TasaDiaria`
- **Propósito**: Publicar una nueva tasa diaria. Cierra la vigente (vigente_hasta = now()) y crea la nueva en una transacción.
- **Parámetros**:
  - `$payload` (array): `tasa_venta`, `tasa_compra`, `moneda_base_id`, `moneda_cotizada_id`, opcionales `fecha`, `tasa_compra_minima`, `tasa_venta_minima`, `notas`.
  - `$admin` (User): usuario que define la tasa (se asigna a `definida_por_id`).
- **Lógica**:
  1. Valida que `tasa_venta >= tasa_compra`. Si no, exige `notas` con ≥10 caracteres; si no hay, lanza `ValidationException`.
  2. Transacción DB:
     a. `UPDATE TasaDiaria SET vigente_hasta = now()` para la fila actualmente vigente (where null `vigente_hasta`), filtrada por par.
     b. `INSERT` nueva TasaDiaria con `vigente_desde = now()`, `vigente_hasta = null`.
- **Retorna**: `TasaDiaria` recién creada.
- **Excepciones**: `ValidationException` si `tasa_venta < tasa_compra` sin notas suficientes.

---

### `obtenerVigente(int $monedaBaseId, int $monedaCotizadaId, ?Carbon $momento = null): ?TasaDiaria`
- **Propósito**: Obtener la tasa vigente para un par en un momento dado.
- **Lógica**:
  1. Si `$momento` es null, usa `now()`.
  2. Query: `vigente_desde <= $momento AND (vigente_hasta IS NULL OR vigente_hasta > $momento)`.
  3. Ordena por `vigente_desde DESC`, trae la primera.
- **Retorna**: `TasaDiaria|null`
- **Modelos**: `TasaDiaria`

---

### `obtenerUltimaPublicada(int $monedaBaseId, int $monedaCotizadaId): ?TasaDiaria`
- **Propósito**: Obtener la última tasa publicada (sin importar vigencia). Usado como fallback (opción C).
- **Lógica**: Query simple: `WHERE moneda_base_id = ? AND moneda_cotizada_id = ? ORDER BY vigente_desde DESC, id DESC LIMIT 1`
- **Retorna**: `TasaDiaria|null`

---

### `validarTasaEfectiva(TasaDiaria $sugerida, float $tasaEfectiva, string $direccion): array`
- **Propósito**: Validar si la tasa efectiva del operador es desfavorable contra los mínimos configurados en la tasa diaria.
- **Parámetros**:
  - `$sugerida` (TasaDiaria): la tasa de referencia (vigente o última).
  - `$tasaEfectiva` (float): tasa que el operador aplicó realmente.
  - `$direccion` (string): `'venta'` o `'compra'`.
- **Lógica**:
  1. Delega en `$sugerida->esDesfavorableParaLaCasa($tasaEfectiva, $direccion)`.
     - `venta`: desfavorable si `tasaEfectiva < tasa_venta_minima` (casa vende más barato de lo que debería).
     - `compra`: desfavorable si `tasaEfectiva > tasa_compra_minima` (casa compra más caro de lo que debería).
     - Si el mínimo es null → nunca desfavorable.
  2. Si no es desfavorable → retorna `es_valida: true, requiere_justificacion: false`.
  3. Si es desfavorable → retorna `es_valida: false, requiere_justificacion: true` con mensaje indicando qué mínimo se violó.
- **Retorna**: `['es_valida' => bool, 'es_desfavorable' => bool, 'requiere_justificacion' => bool, 'mensaje' => ?string]`

---

### `direccionDeTipo(string $codigoTipo): ?string`
- **Propósito**: Mapear el código del tipo de operación a dirección (`venta`/`compra`/null).
- **Lógica**: `match`:
  - `'venta_usd'` → `'venta'`
  - `'compra_usd'` → `'compra'`
  - default → `null` (traslado, gasto, ajuste, cambio, comision, intermediada no tienen dirección)
- **Retorna**: `string|null`

---

### `identificarPar(array $movimientos): array`
- **Propósito**: Resolver el par (moneda_base_id, moneda_cotizada_id) a partir de los movimientos de una operación. Implementa Reglas A, B y C.
- **Parámetros**:
  - `$movimientos` (array de `['moneda_id' => int, 'monto' => float]`)
- **Lógica**:
  1. Obtiene `$usdId = Moneda::where('codigo', 'USD')->value('id')`.
  2. Extrae monedas únicas de los movimientos.
  3. **REGLA A** (1 moneda distinta): `par = moneda / USD` (ej: gasto en VES → VES/USD).
  4. **REGLA B** (2 monedas distintas):
     a. Si una es VES (moneda local de config `sistema.moneda_local`, default `'VES'`): `par = otra_moneda / VES`. Esto asegura que venta_usd (-USD, +VES) y compra_usd (+USD, -VES) resuelvan el mismo par (USD/VES).
     b. Si no hay VES: usa signo del monto para definir: negativo = base, positivo = cotizada.
  5. **REGLA C** (3+ monedas): lanza `ValidationException` pidiendo desglosar en operaciones encadenadas.
- **Retorna**: `['moneda_base_id' => int, 'moneda_cotizada_id' => int]`
- **Excepciones**: `ValidationException` si hay 3+ monedas distintas.

---

## `CalculadorComisionesService`

- **File**: `api/app/Services/Configuracion/CalculadorComisionesService.php`
- **Namespace**: `App\Services\Configuracion`
- **Propósito**: Calcular y aplicar comisiones a operaciones. Soporta 3 tipos de comisión: por cuenta, por operador y por método de pago. Porcentajes o montos fijos, conversión a USD, recálculo de totales y ganancia neta.
- **Dependencias inyectadas**: `TasaDiariaService $tasaService`
- **Modelos que usa**: `ComisionCuenta`, `ComisionOperador`, `ComisionMetodoPago`, `ComisionOperacion`, `Moneda`, `Operacion`, `TasaMercado`
- **Excepciones**: Ninguna propagada explícitamente (usa `find` que puede lanzar `ModelNotFoundException` si no existe moneda, pero `Moneda::find($id)` no la lanza — retorna null).

---

### `calcularParaOperacion(Operacion $op): Collection`
- **Propósito**: Calcular todas las comisiones aplicables a una operación. NO persiste nada.
- **Lógica**: 3 fases:

  **Fase 1 — Comisiones de Cuenta** (`ComisionCuenta`):
  1. Itera cada movimiento de la operación.
  2. Determina `dirección = ingreso` (monto > 0) o `egreso` (monto < 0).
  3. Busca `ComisionCuenta::where('activa', true)` que matcheen por `cuenta_id` o `banco_id`, y donde `aplica_a` sea `$direccion` o `'ambos'`, y vigentes en la fecha de la operación.
  4. Para cada coincidencia:
     - Si `tipo_calculo = 'porcentaje'`: `monto = abs(monto_movimiento) * (valor / 100)`.
     - Si `tipo_calculo = 'fijo'`: `monto = valor`.
     - `monto_usd_equivalente`: si la moneda de la comisión = moneda del movimiento, multiplica por `movimiento->tasa_a_usd`; si no, llama a `convertirAUsd()`.
  5. Push al collection con: `tipo='cuenta'`, `origen_model`, `descripcion`, `monto`, `moneda_id`, `monto_usd_equivalente`, `movimiento_id`.

  **Fase 2 — Comisiones de Operador** (`ComisionOperador`):
  1. Si la operación tiene operador con `titular_id`:
     a. Busca `ComisionOperador::where('titular_id', $operador->titular_id)` activa, vigente, y que aplique al `tipo_operacion_id` (o sea null = aplica a todos).
     b. Calcula `$montoOperacion = sum(monto_usd_equivalente` de movimientos positivos).
     c. Para cada comisión:
        - `base = ganancia_bruta_usd` (si `base_calculo = 'ganancia_bruta'`) o `$montoOperacion`.
        - Monto: porcentaje de la base o valor fijo.
        - `montoUsd = convertirAUsd(monto, comision->moneda_id, op->fecha)`.
  2. Push al collection con `movimiento_id = null`.

  **Fase 3 — Comisiones de Método de Pago** (`ComisionMetodoPago`):
  1. Obtiene `cuentaIds` únicos de los movimientos.
  2. Busca `ComisionMetodoPago::where('activa', true)` que matcheen por `cuenta_id` null (global) o en los ids de las cuentas, vigentes.
  3. Para cada una:
     - Si tiene `cuenta_id` específico, filtra solo los movimientos de esa cuenta; si no, afecta a todos.
     - `totalMovs = sum(abs(monto))` de los movimientos afectados.
     - Monto: porcentaje del total o valor fijo.
     - `montoUsd = convertirAUsd()`.
  4. Push con `movimiento_id = null`.

- **Retorna**: `Collection` de arrays planos (no son modelos).

---

### `aplicarAOperacion(Operacion $op): void`
- **Propósito**: Calcular y persistir comisiones. Idempotente (borra anteriores primero). Luego recalcula totales.
- **Lógica**:
  1. Transacción DB.
  2. `DELETE FROM comisiones_operacion WHERE operacion_id = ?`.
  3. Llama `calcularParaOperacion()`.
  4. `INSERT` cada comisión como `ComisionOperacion`.
  5. Llama `recalcularTotalesOperacion()`.
- **Retorna**: `void`

---

### `recalcularTotalesOperacion(Operacion $op): void`
- **Propósito**: Sumar todas las comisiones de la operación y actualizar campos de totales y ganancia neta en la operación.
- **Lógica**:
  1. Load `comisiones.moneda`.
  2. `$totalUsd = sum(monto_usd_equivalente)` de todas las comisiones.
  3. `$totalVes`: suma directa si `moneda->codigo === 'VES'`, si no convierte `monto_usd_equivalente * tasa_aplicada` (usa `tasa_aplicada ?? tasa_sugerida` de la operación).
  4. `UPDATE operacion SET total_comisiones_usd, total_comisiones_ves, ganancia_neta_usd = ganancia_bruta_usd - total_comisiones_usd, ganancia_neta_ves = ganancia_bruta_ves - total_comisiones_ves`.
- **Retorna**: `void`

---

### `editarComision(ComisionOperacion $comision, array $nuevoValor, \App\Models\User $admin, string $razon): void`
- **Propósito**: Editar una comisión ya aplicada (admin/super_admin). Guarda quién editó, cuándo y por qué, y recalcula totales.
- **Lógica**:
  1. Transacción DB.
  2. `UPDATE comision_operacion SET monto, monto_usd_equivalente, descripcion, editada_por_id, editada_at, razon_edicion`.
  3. Llama `recalcularTotalesOperacion($comision->operacion)`.
- **Retorna**: `void`

---

### (privado) `calcularMontoOperacion(Operacion $op): float`
- Suma de `monto_usd_equivalente` de movimientos positivos (ingresos).
- **Retorna**: `float`

### (privado) `convertirAUsd(float $monto, int $monedaId, Carbon|string $fecha): float`
- **Propósito**: Convertir un monto a USD usando la tasa diaria vigente en la fecha; fallback a `TasaMercado::where('fuente', 'bcv')` más reciente; si no hay tasa, retorna 0.
- **Lógica**:
  1. Si `moneda->codigo === 'USD'` → retorna `$monto`.
  2. Busca tasa vigente para `par = monedaId / USD` al `endOfDay()` de la fecha.
  3. Si existe: `$monto / tasa_venta`.
  4. Fallback: última `TasaMercado` de BCV anterior a la fecha → `$monto / valor`.
  5. Si no: 0.0 (visible para auditoría).
- **Retorna**: `float`

---

## `RegistroOperacionService`

- **File**: `api/app/Services/Operaciones/RegistroOperacionService.php`
- **Namespace**: `App\Services\Operaciones`
- **Propósito**: Registrar y actualizar operaciones de negocio con movimientos contables. Maneja validación de partida doble, resolución de tasa diaria, cálculo de ganancia bruta, aplicación de comisiones, dispatch de jobs de FIFO y saldo cache.
- **Dependencias inyectadas**: `TasaDiariaService $tasaService`, `CalculadorComisionesService $comisionesService`
- **Modelos que usa**: `TipoOperacion`, `Operacion`, `Cuenta`, `Moneda`
- **Jobs que despacha**: `ProcesarFifoOperacionJob`, `RecalcularSaldoCuentaJob` (implícito en `saldo_cache_at` update)
- **Constantes**: `TOLERANCIA_USD = 0.01`

---

### `registrar(array $payload): Operacion`
- **Propósito**: Registrar una operación completa con su flujo completo.
- **Parámetros**:
  - `$payload` (array): `tipo_codigo`, `fecha`, `operador_id`, `movimientos` (array de `['cuenta_id', 'monto', 'tasa_a_usd?']`), `tasa_aplicada?`, `cliente_id?`, `categoria_gasto_id?`, `genera_comision?`, `referencia?`, `descripcion?`, `origen?`, `tasa_mercado_snapshot?`, `fuente_tasa_mercado?`, etc.
- **Lógica**:
  1. Busca `TipoOperacion::where('codigo', $payload['tipo_codigo'])` → `firstOrFail()`.
  2. **Si es `intermediada`**: deriva a `registrarIntermediada()` (no usa tasa diaria).
  3. **Auto-cálculo de `tasa_a_usd`**: para cada movimiento sin `tasa_a_usd`:
     - USD/USDT → 1.0.
     - Otras monedas → `1 / tasa_aplicada` (redondeado a 8 decimales).
  4. `validarMovimientos($payload['movimientos'], $tipo)`.
  5. `resolverTasa($payload, $tipo)` → obtiene `[$tasaDiaria, $tasaSugerida, $tasaEfectiva, $sinTasaReferencia]`.
  6. **Transacción DB**:
     a. `Operacion::create(...)` con todos los campos.
     b. Itera `movimientos`, obtiene `Cuenta::findOrFail()`, crea `Movimiento` con `moneda_id` desde la cuenta (no del payload).
     c. Si `$tipo->genera_ganancia`: carga movimientos con moneda, calcula ganancia bruta, actualiza.
     d. Aplica comisiones: `$this->comisionesService->aplicarAOperacion($operacion)`.
     e. Actualiza saldo_cache de cuentas: `bcadd(saldo_cache, monto, 4)` (solo si `saldo_cache_at` no es null).
     f. Si `$tipo->afecta_fifo`: despacha `ProcesarFifoOperacionJob`.
  7. Retorna `$operacion->fresh(['movimientos.cuenta', 'tipoOperacion'])`.
- **Retorna**: `Operacion` (con relaciones cargadas)
- **Excepciones**:
  - `ModelNotFoundException` si `TipoOperacion` no existe.
  - `ValidationException` desde `validarMovimientos()` o `resolverTasa()`.

---

### `actualizar(Operacion $operacion, array $payload, \App\Models\User $editor): Operacion`
- **Propósito**: Actualizar una operación existente (solo si es editable — validado en FormRequest).
- **Lógica**:
  1. **Transacción DB**:
     a. Actualiza campos básicos (`fecha`, `cliente_id`, `categoria_gasto_id`, `operador_id`, `tasa_aplicada`, `genera_comision`, `monto_comision`, `tipo_comision`, `tasa_mercado_snapshot`, `fuente_tasa_mercado`, `referencia`, `descripcion`). Registra cambios en `$cambios[]`.
     b. **Si vienen movimientos nuevos**: auto-calcula `tasa_a_usd` (como en `registrar`), valida movimientos, elimina anteriores, crea nuevos.
     c. `$operacion->save()`.
     d. Si `$tipo->genera_ganancia`: recalcula ganancia bruta.
     e. Re-aplica comisiones (`$this->comisionesService->aplicarAOperacion`).
     f. Si hubo cambios: registra en bitácora con `activity()` (spatie `laravel-activitylog`): `performedOn`, `causedBy`, `properties` con `cambios` y `motivo_edicion`.
     g. Actualiza saldo_cache de cuentas afectadas (solo si hay movimientos nuevos).
     h. Si `$tipo->afecta_fifo`: despacha `ProcesarFifoOperacionJob`.
- **Retorna**: `Operacion` (fresh con relaciones)
- **Precondición**: Se espera que el FormRequest ya haya validado que la operación no está verificada ni cancelada.

---

### (privado) `resolverTasa(array $payload, TipoOperacion $tipo): array`
- **Propósito**: Resolver tasa sugerida, efectiva, diaria_id y flag `sin_tasa_referencia`.
- **Lógica**:
  1. `$direccion = $this->tasaService->direccionDeTipo($tipo->codigo)`.
  2. Sin dirección → retorna `[null, null, $payload['tasa_aplicada']??null, false]`.
  3. Con dirección:
     a. Enriquece movimientos con `moneda_id` desde `Cuenta::find()`.
     b. `$par = $this->tasaService->identificarPar($movsConMoneda)`.
     c. Busca tasa vigente para el par → si no existe, busca última publicada.
        - Si ninguna existe: `ValidationException` con mensaje "No existe tasa configurada para el par X/Y".
        - Si existe pero no es vigente: `$sinTasaReferencia = true`.
     d. `$tasaSugerida = $direccion === 'venta' ? tasa_venta : tasa_compra`.
     e. `$tasaEfectiva` de `payload['tasa_aplicada']` o fallback a `$tasaSugerida`.
     f. **Validación de tasa desfavorable**: si `$tasaDiaria->esDesfavorableParaLaCasa($tasaEfectiva, $direccion)` → exige justificación en `payload['descripcion']`. Si está vacía, lanza `ValidationException`.
- **Retorna**: `[?TasaDiaria, ?float, ?float, bool]`
- **Excepciones**: `ValidationException` (sin tasa o tasa desfavorable sin justificación)

---

### (privado) `validarMovimientos(array $movs, TipoOperacion $tipo): void`
- **Propósito**: Validar movimientos según reglas de negocio.
- **Reglas**:
  1. **Siempre**: ninguna `cuenta_id` puede ser de cuenta inactiva (`Cuenta::where('activa', false)`). Si hay, `ValidationException` listando alias.
  2. **`ajuste_apertura`**: exactamente 1 movimiento.
  3. **`gasto`, `comision`, `ajuste`**: mínimo 1 movimiento. Sin validación de cuadre.
  4. **`venta_usd`, `compra_usd`, `cambio`, `traslado`**: mínimo 2 movimientos. Además `|Σ(monto × tasa_a_usd)| ≤ TOLERANCIA_USD (0.01)`. Si no, `ValidationException` con diferencia exacta.
- **Excepciones**: `ValidationException`

---

### (privado) `calcularGananciaBruta(Operacion $operacion): array`
- **Propósito**: Calcular ganancia bruta (snapshot congelado). No recalcula después.
- **Lógica por tipo**:
  - **`venta_usd`**: `ganancia_ves = monto_usd_vendido × (tasa_aplicada − tasa_mercado_snapshot)`; `ganancia_usd = ganancia_ves / tasa_aplicada`.
    - Si falta `tasa_mercado_snapshot` o `tasa_aplicada` → 0.
  - **`compra_usd`**: `ganancia_ves = monto_usd_comprado × (tasa_mercado_snapshot − tasa_aplicada)`; `ganancia_usd = ganancia_ves / tasa_mercado_snapshot`.
    - Si falta snapshot o aplicada → 0.
  - **`comision`**: Usa el primer movimiento de ingreso. `ganancia_usd = monto_usd_equivalente`; si moneda es VES, `ganancia_ves = monto`; si no, convierte con `tasa_mercado_snapshot`.
  - **`cambio`**: `[0, 0]` (TODO Fase 4, implementar en FifoService).
  - **default** (traslado, gasto, ajuste, ajuste_apertura): `[0, 0]`.
- **Retorna**: `['usd' => float, 'ves' => float]`

---

### (privado) `registrarIntermediada(array $payload, TipoOperacion $tipo): Operacion`
- **Propósito**: Registrar operación intermediada (cliente emisor ↔ casa ↔ cliente receptor). No usa tasa diaria ni cuadre contable estricto.
- **Lógica**:
  1. `validarMovimientosIntermediada($payload['movimientos'])` (≥4 mov, cuentas activas).
  2. Transacción:
     a. Crea Operacion con `cliente_emisor_id`, `cliente_receptor_id`, `tasa_compra`, `tasa_venta`, `tasa_aplicada = null`.
     b. Crea movimientos.
     c. `calcularGananciaBrutaIntermediada()`.
     d. Aplica comisiones.
     e. Actualiza saldo_cache.
  3. Retorna `fresh()`.
- **Retorna**: `Operacion`

### (privado) `validarMovimientosIntermediada(array $movs): void`
- ≥4 movimientos, cuentas activas. No valida cuadre.

### (privado) `calcularGananciaBrutaIntermediada(Operacion $operacion): array`
- `montoDivisa = sum(abs(monto)) de movimientos en USD/USDT/EUR/COP / 2`.
- `gananciaVes = montoDivisa × (tasa_venta − tasa_compra)`.
- `gananciaUsd = gananciaVes / tasa_venta`.

---

## `ReporteComisionesOperadoresService`

- **File**: `api/app/Services/Reportes/ReporteComisionesOperadoresService.php`
- **Namespace**: `App\Services\Reportes`
- **Propósito**: Generar y exportar reportes de comisiones de operadores agrupados por titular. Soporta Excel y PDF.
- **Dependencias**: `Maatwebsite\Excel\Facades\Excel`, `Barryvdh\DomPDF\Facade\Pdf`, `Illuminate\Support\Facades\Storage`
- **Modelos que usa**: `ComisionOperacion` (con relaciones `operacion`, `moneda`, `origen` — que es el modelo polimórfico, en este caso `ComisionOperador`)
- **Export**: `\App\Exports\ComisionesOperadoresExport`
- **Vista**: `reportes.comisiones_operadores`

---

### `generar($desde, $hasta): Collection`
- **Propósito**: Generar el reporte en memoria, agrupado por titular.
- **Parámetros**:
  - `$desde` (Carbon): fecha inicio (inclusive).
  - `$hasta` (Carbon): fecha fin (inclusive).
- **Lógica**:
  1. Query a `ComisionOperacion::where('tipo', 'operador')` que tenga operación con `fecha BETWEEN [$desde, $hasta]`. Eager load `operacion`, `moneda`, `origen` (relación polimórfica a `ComisionOperador`).
  2. `groupBy(fn($c) => $c->origen->titular_id)`.
  3. Filtra grupos con `titular_id !== null`.
  4. Para cada grupo: calcula `total_operaciones` (operaciones distintas), `total_comisiones_usd` (suma de `monto_usd_equivalente`).
  5. Incluye `detalle` con la colección original del grupo.
- **Retorna**: `Collection` de `['titular_id', 'titular' => string, 'total_operaciones' => int, 'total_comisiones_usd' => float, 'detalle' => Collection]`

---

### `exportarExcel($desde, $hasta): string`
- **Propósito**: Generar y persistir archivo Excel del reporte.
- **Lógica**:
  1. Obtiene datos via `$this->generar()`.
  2. Determina `$mes = $desde->format('Y-m')`.
  3. Path: `config('reportes.comisiones_operadores.storage_path', 'reportes/comisiones') . "/comisiones_operadores_{$mes}.xlsx"`.
  4. `Excel::store(new ComisionesOperadoresExport($datos, $desde, $hasta), $path, 'local')`.
- **Retorna**: `string` (path relativo en storage, usable con `Storage::url()`).

---

### `exportarPdf($desde, $hasta): string`
- **Propósito**: Generar y persistir archivo PDF del reporte.
- **Lógica**:
  1. Obtiene datos via `$this->generar()`.
  2. Path similar a Excel pero con extensión `.pdf`.
  3. Renderiza vista `reportes.comisiones_operadores` con `$datos`, `$desde`, `$hasta`.
  4. `Pdf::loadView(...)->output()` → `Storage::put($path, $output)`.
- **Retorna**: `string` (path relativo en storage).

---

## Resumen de dependencias entre servicios

```
TasasMercadoService          (independiente, solo Http + Log)
       │
       ▼
TasaDiariaService            (independiente, solo modelos propios)
       │
       ▼
CalculadorComisionesService  ← TasaDiariaService (para convertir a USD)
       │
       ▼
RegistroOperacionService     ← TasaDiariaService + CalculadorComisionesService
       │
       ├── despacha → ProcesarFifoOperacionJob
       └── despacha → RecalcularSaldoCuentaJob (implícito, actualiza saldo_cache)

ReporteComisionesOperadoresService  (independiente, solo ComisionOperacion + Excel/PDF)
```

## Modelos clave referenciados

| Modelo | Campos relevantes |
|---|---|
| `TasaDiaria` | `moneda_base_id`, `moneda_cotizada_id`, `tasa_compra`, `tasa_venta`, `tasa_compra_minima`, `tasa_venta_minima`, `vigente_desde`, `vigente_hasta`, `definida_por_id`, `notas` |
| `ComisionCuenta` | `cuenta_id`, `banco_id`, `aplica_a` (ingreso/egreso/ambos), `tipo_calculo` (porcentaje/fijo), `valor`, `moneda_id`, `vigente_desde`, `vigente_hasta`, `activa` |
| `ComisionOperador` | `titular_id`, `tipo_operacion_id` (nullable), `base_calculo` (ganancia_bruta/monto_operacion), `tipo_calculo`, `valor`, `moneda_id`, `vigente_desde`, `vigente_hasta`, `activa` |
| `ComisionMetodoPago` | `nombre_metodo`, `cuenta_id` (nullable=global), `tipo_calculo`, `valor`, `moneda_id`, `vigente_desde`, `vigente_hasta`, `activa` |
| `ComisionOperacion` | `operacion_id`, `tipo` (cuenta/operador/metodo_pago), `origen_type`, `origen_id`, `monto`, `moneda_id`, `monto_usd_equivalente`, `movimiento_id` (nullable), `editada_por_id`, `editada_at`, `razon_edicion` |
| `Operacion` | `fecha`, `tipo_operacion_id`, `operador_id`, `tasa_aplicada`, `tasa_sugerida`, `tasa_diaria_id`, `sin_tasa_referencia`, `tasa_mercado_snapshot`, `fuente_tasa_mercado`, `ganancia_bruta_usd`, `ganancia_bruta_ves`, `total_comisiones_usd`, `total_comisiones_ves`, `ganancia_neta_usd`, `ganancia_neta_ves`, `estatus`, `estado_pool` |
| `Moneda` | `codigo` (USD, VES, USDT, EUR, COP...) |
| `Cuenta` | `moneda_id`, `banco_id`, `alias`, `activa`, `saldo_cache`, `saldo_cache_at` |
| `TipoOperacion` | `codigo`, `genera_ganancia`, `afecta_fifo` |
| `TasaMercado` | `fuente`, `valor`, `capturado_en` |

---

## `PoolNotifier`

- **File**: `api/app/Services/Pool/PoolNotifier.php`
- **Namespace**: `App\Services\Pool`
- **Propósito**: Notificaciones de eventos del pool de pagos (asignación, pago, cancelación, SLA).
- **Métodos**:
  - `operacionesAsignadas(Collection $operaciones, User $pagador)` → Log `info` con IDs de operaciones y pagador
  - `operacionPagada(Operacion $operacion, User $pagador)` → Log `info`
  - `operacionCancelada(Operacion $operacion, User $usuario, string $motivo)` → Log `warning`
  - `slaExcedida(Operacion $operacion, int $minutosEspera)` → Log `warning` con minutos de espera
- **Estado actual**: Solo log por ahora. Puede extenderse a email/push/WebSocket. La notificación en tiempo real al frontend se maneja vía `SlaExcedida` event (broadcast Reverb).
