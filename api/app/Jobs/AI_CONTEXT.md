# Jobs — AI Context

## `AlertarTasasFaltantesJob`
- **File**: `api/app/Jobs/AlertarTasasFaltantesJob.php`
- **Schedule**: Diario 08:00 (`alertar-tasas-faltantes-manana`) y 14:00 (`alertar-tasas-faltantes-tarde`)
- **Tries**: 2
- **Timeout**: default (ninguno definido)
- **Queue**: default
- **Dispatcher**: `Schedule::job(new AlertarTasasFaltantesJob())` desde `routes/console.php`

### Lógica
1. Obtiene `config('sistema.pares_principales', ['USD/VES', 'USDT/VES'])` — lista de pares a verificar
2. Por cada par (ej: `USD/VES`):
   - **Explode** con `/` → `$codigoBase`, `$codigoCotizada`
   - Busca `Moneda::where('codigo', $codigoBase)->first()` — si no existe, **skip**
   - Busca `Moneda::where('codigo', $codigoCotizada)->first()` — si no existe, **skip**
   - Verifica si existe `TasaDiaria` vigente:
     ```sql
     WHERE moneda_base_id = $monedaBase->id
       AND moneda_cotizada_id = $monedaCotizada->id
       AND vigente_hasta IS NULL
       AND DATE(fecha) = today()
     ```
   - Si `!exists()`, agrega el par a `$paresFaltantes`
3. Si `$paresFaltantes` está vacío → `return` (sin logging ni email)
4. Si hay faltantes:
   - `Log::warning("AlertarTasasFaltantesJob: pares sin tasa del día: {$listaTexto}")`
   - Busca admins: `User::role(['admin', 'super_admin'])->where('activo', true)->whereNotNull('email')->get()` (usa Spatie Permission)
   - Por cada admin:
     - `Mail::raw()` con mensaje plano:
       - **Subject**: `"⚠️ Falta publicar tasa del día para: {$listaTexto}"`
       - **Body**: lista de pares faltantes + instrucción de publicar en panel
     - Captura `Throwable` por cada envío, no interrumpe el resto

### Modelos/Servicios
- `App\Models\Moneda` — consulta por código
- `App\Models\TasaDiaria` — consulta tasa vigente del día
- `App\Models\User` — spatie/laravel-permission (`role()` scope)

### Logging
- `Log::warning` cuando existen pares faltantes
- `Log::error` si falla el envío de email a un admin (`"no se pudo enviar email a {$email}: {$msg}"`)

### Excepciones
- `\Throwable` capturado al enviar cada email — no relanza, no interrumpe el batch

### Queries
- `Moneda::where('codigo', ...)->first()` (por cada par)
- `TasaDiaria::where(...)->whereNull('vigente_hasta')->whereDate('fecha', today())->exists()`
- `User::role([...])->where('activo', true)->whereNotNull('email')->get()`

---

## `SincronizarTasasJob`
- **File**: `api/app/Jobs/SincronizarTasasJob.php`
- **Schedule**: Cada minuto, `->withoutOverlapping()`, name `sincronizar-tasas`
- **Tries**: 3
- **Backoff**: 30 segundos entre reintentos
- **Timeout**: default
- **Queue**: default
- **Dispatcher**: `Schedule::job(new SincronizarTasasJob())->everyMinute()->withoutOverlapping()`

### Lógica
1. Inyecta `TasasMercadoService $service` via **autowiring** en `handle()`
2. Ejecuta 4 fuentes en paralelo (llamadas secuenciales pero sin dependencia entre sí):
   - `$service->obtenerBcv()` → `['fuente' => 'bcv', 'par' => 'USD/VES', 'valor' => float, 'payload' => array]` o `null`
   - `$service->obtenerParalelo()` → `['fuente' => 'paralelo', 'par' => 'USD/VES', ...]` o `null`
   - `$service->obtenerBinanceP2P('BUY')` → `['fuente' => 'binance_p2p_buy', 'par' => 'USDT/VES', ...]` o `null`
   - `$service->obtenerBinanceP2P('SELL')` → `['fuente' => 'binance_p2p_sell', 'par' => 'USDT/VES', ...]` o `null`
3. Precarga IDs de monedas en 1 query:
   ```php
   Moneda::whereIn('codigo', ['USD', 'VES', 'USDT'])->pluck('id', 'codigo')
   ```
4. Por cada resultado no nulo:
   - Extrae `$codigoBase` y `$codigoCotizada` del `$resultado['par']` (explode '/')
   - Resuelve IDs desde `$monedaIds`; si falta alguno → `Log::warning` + `continue`
   - `TasaMercado::create([...])` con:
     - `fuente`, `moneda_base_id`, `moneda_cotizada_id`, `valor`, `capturado_en` (now), `payload_original`
   - Captura `Throwable` al guardar → `Log::error`
   - Cachea en Redis/File por 30 min:
     ```php
     Cache::put("tasa_actual:{$resultado['fuente']}", $cached, now()->addMinutes(30))
     ```
     - `$cached` = merge de `$resultado` + `capturado_en` (ISO8601), **sin** `payload`

### Modelos/Servicios
- `App\Models\Moneda` — `pluck('id', 'codigo')` precarga
- `App\Models\TasaMercado` — persistencia de cada captura
- `App\Services\Tasas\TasasMercadoService` — fetch de fuentes externas
  - `obtenerBcv()`: HTTP GET `https://ve.dolarapi.com/v1/dolares/oficial`, timeout 10s, 2 retries 500ms
  - `obtenerParalelo()`: HTTP GET `https://ve.dolarapi.com/v1/dolares/paralelo`, timeout 10s, 2 retries 500ms
  - `obtenerBinanceP2P(tradeType, top=10)`: HTTP POST `https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search`, timeout 10s, 2 retries 500ms; calcula promedio, mediana, min, max, #muestras
- `Illuminate\Support\Facades\Cache` — `Cache::put("tasa_actual:{$fuente}", ...)` TTL 30 min

### Logging
- `Log::warning` cuando no se encuentra moneda para un par
- `Log::error` si falla `TasaMercado::create` (por fuente)
- Interno en `TasasMercadoService`:
  - `Log::warning` por cada fuente que falle (`obtenerBcv falló`, `obtenerParalelo falló`, `obtenerBinanceP2P falló`)

### Excepciones
- `\Throwable` capturado al hacer `TasaMercado::create` — no relanza, loggea y sigue
- Interno en `TasasMercadoService`: cada método captura `\Throwable`, retorna `null`

### Queries
- `Moneda::whereIn('codigo', ['USD', 'VES', 'USDT'])->pluck('id', 'codigo')`
- `TasaMercado::create([...])` (inserción por cada fuente)

---

## `SincronizarTasasReferenciaJob`
- **File**: `api/app/Jobs/SincronizarTasasReferenciaJob.php`
- **Schedule**: Cada minuto, `->withoutOverlapping()`, name `sincronizar-tasas-referencia`
- **Tries**: 3
- **Backoff**: 30 segundos
- **Timeout**: default
- **Queue**: default
- **Dispatcher**: `Schedule::job(new SincronizarTasasReferenciaJob())->everyMinute()->withoutOverlapping()`

### Lógica
1. Precalcula `$ahora = now()` y precarga monedas:
   ```php
   Moneda::whereIn('codigo', ['USD', 'VES', 'USDT'])->pluck('id', 'codigo')
   ```
2. Llama a `$this->capturarBcv($monedaIds, $ahora)`
3. Llama a `$this->capturarBinanceP2P($monedaIds, $ahora)`

#### `capturarBcv()`
1. `Http::timeout(10)->retry(2, 500)->get('https://ve.dolarapi.com/v1/dolares/oficial')`
2. `$response->throw()` (lanza excepción si status >= 400)
3. Extrae `$data['promedio'] ?? $data['promedioVenta'] ?? 0`
4. Si `$valor <= 0` → `Log::warning` + `return`
5. `$this->guardar('bcv', usdId, vesId, valor, $ahora, $data)`

#### `capturarBinanceP2P()`
1. `Http::timeout(10)->retry(2, 500)->post(...)` con headers custom (User-Agent) y body:
   ```json
   {"asset":"USDT","fiat":"VES","tradeType":"BUY","page":1,"rows":5,"payTypes":[]}
   ```
2. Toma primeros 3 anuncios (`take(3)`), extrae `price` como float, filtra > 0
3. Si vacío → `Log::warning` + `return`
4. Calcula `$valor = round($precios->avg(), 8)`
5. `$this->guardar('binance_p2p', usdtId, vesId, valor, $ahora, $data)`

#### `guardar()`
- Si `$baseId === null || $cotizadaId === null` → `Log::warning` + `return`
- `TasaMercado::create(['fuente', 'moneda_base_id', 'moneda_cotizada_id', 'valor', 'capturado_en', 'payload_original'])`

### Diferencias con `SincronizarTasasJob`
- **No usa** `TasasMercadoService` — hace HTTP requests directamente
- **No obtiene** tasa paralelo ni Binance SELL
- **No cachea** en Redis/File las tasas actuales
- **No calcula** mediana/min/max/muestras (solo promedio de top 3)
- **Timeout y retries** configurados igual (10s, 2 retries 500ms)
- **Tries/backoff**: igual (3 tries, 30s backoff)
- **Schedule**: ambos cada minuto sin overlapping

### Modelos/Servicios
- `App\Models\Moneda` — precarga IDs
- `App\Models\TasaMercado` — persistencia
- `Illuminate\Support\Facades\Http` — llamadas directas

### Logging
- `Log::warning` cuando BCV responde sin valor válido
- `Log::warning` cuando BCV falla (excepción)
- `Log::warning` cuando Binance P2P sin precios válidos
- `Log::warning` cuando Binance P2P falla (excepción)
- `Log::warning` si moneda no encontrada en `guardar()`

### Excepciones
- `\Throwable` capturado en cada método `capturar*` — no relanza
- `$response->throw()` lanza `Illuminate\Http\Client\RequestException` si HTTP error — pero es capturado por el `catch` de `\Throwable`

### Queries
- `Moneda::whereIn('codigo', ['USD', 'VES', 'USDT'])->pluck('id', 'codigo')`
- `TasaMercado::create([...])` (2 inserciones: bcv + binance_p2p)

---

## `GenerarReporteMensualComisionesJob`
- **File**: `api/app/Jobs/GenerarReporteMensualComisionesJob.php`
- **Schedule**: Mensual día 1 a las 06:00, `->withoutOverlapping()`, name `reporte-mensual-comisiones`
- **Tries**: 3
- **Timeout**: 300 segundos (5 min)
- **Queue**: default
- **Dispatcher**: `Schedule::job(new GenerarReporteMensualComisionesJob())->monthlyOn(1, '06:00')->withoutOverlapping()`

### Lógica
1. Inyecta `ReporteComisionesOperadoresService $service` vía autowiring en `handle()`
2. Calcula período del mes anterior:
   - `$desde = now()->subMonth()->startOfMonth()`
   - `$hasta = now()->subMonth()->endOfMonth()`
3. `Log::info("GenerarReporteMensualComisionesJob: generando reporte {$desde->format('Y-m')}")`
4. **Dentro de try/catch (\Throwable):**
   - `$service->exportarExcel($desde, $hasta)` → genera Excel vía `Maatwebsite\Excel` + `App\Exports\ComisionesOperadoresExport`
   - `$service->exportarPdf($desde, $hasta)` → genera PDF vía `Barryvdh\DomPDF` + view `reportes.comisiones_operadores`
   - `Log::info` con paths generados
5. Si `config('reportes.comisiones_operadores.enviar_email') === true`:
   - Lee destinatarios de `config('reportes.comisiones_operadores.destinatarios', '')`
   - Si vacío → `Log::warning` + `return` temprano
   - `array_filter(array_map('trim', explode(',', $destinatariosRaw)))` → parsea lista separada por comas
   - `Mail::raw(...)`:
     - **Subject**: `"Reporte comisiones operadores — {$desde->format('M Y')}"`
     - **Body**: texto plano informando que el reporte fue generado, pide adjuntar archivos manualmente
   - Captura `Throwable` al enviar email → `Log::error`
6. Si falla la generación del reporte (Excel/PDF):
   - `Log::error` con mensaje y `['exception' => $e]`
   - **Relanza** `$e` para que Laravel marque el job como `failed`

### Servicios
- `App\Services\Reportes\ReporteComisionesOperadoresService`
  - `generar($desde, $hasta)`: consulta `ComisionOperacion` con `whereHas('operacion', ...)` filtrando por `fecha BETWEEN`, `tipo = 'operador'`, eager load `['operacion', 'moneda', 'origen']`; agrupa por `origen.titular_id`, mapea a `{titular_id, titular, total_operaciones, total_comisiones_usd, detalle}`
  - `exportarExcel($desde, $hasta)`: genera Excel con `ComisionesOperadoresExport` y almacena en `storage_path` configurable
  - `exportarPdf($desde, $hasta)`: genera PDF con view blade y almacena
- `App\Exports\ComisionesOperadoresExport` (clase de exportación Laravel Excel)
- `Barryvdh\DomPDF\Facade\Pdf`

### Logging
- `Log::info` al inicio de la generación
- `Log::info` con paths de archivos generados
- `Log::warning` si `enviar_email=true` pero no hay destinatarios
- `Log::error` si falla envío de email
- `Log::error` si falla generación del reporte (relanza)

### Excepciones
- `\Throwable` capturado en generación de reporte → log + **relanza** (el job se marca como failed)
- `\Throwable` capturado en envío de email → log + **no relanza**

### Queries (dentro del Service)
- `ComisionOperacion::whereHas('operacion', fn($q) => $q->whereBetween('fecha', [$desde, $hasta]))->where('tipo', 'operador')->with(['operacion', 'moneda', 'origen'])->get()`

---

## `AutoArchivarClientesInactivos`
- **File**: `api/app/Jobs/AutoArchivarClientesInactivos.php`
- **Schedule**: Semanal domingo 03:00, `->withoutOverlapping()`, name `auto-archivar-clientes-inactivos`
- **Tries**: 1
- **Timeout**: default
- **Queue**: default
- **Dispatcher**: `Schedule::job(new AutoArchivarClientesInactivos())->weeklyOn(0, '03:00')->withoutOverlapping()`

### Lógica
1. Lee `config('sistema.clientes_meses_inactividad', 4)` — meses de inactividad antes de archivar
2. `$fechaLimite = now()->subMonths($mesesInactividad)`
3. Query para obtener clientes archivables:
   ```php
   Cliente::whereNull('deleted_at')
       ->whereDoesntHave('operaciones', function ($q) use ($fechaLimite) {
           $q->where('fecha', '>=', $fechaLimite);
       })
       ->get()
   ```
   - Clientes sin soft-delete y sin ninguna operación con `fecha >= $fechaLimite`
4. Itera cada cliente:
   - `$cliente->delete()` (soft-delete, actualiza `deleted_at`)
   - `$contador++`
   - `Log::info("AutoArchivarClientesInactivos: cliente #{$id} ({$nombre}) archivado.")`
   - Captura `Throwable` → `Log::error` + continúa con el siguiente
5. `Log::info("AutoArchivarClientesInactivos: {$contador} cliente(s) archivado(s).")`

### Modelos
- `App\Models\Cliente` — consulta y soft-delete
- `App\Models\Operacion` — relación `operaciones()` usada en subquery

### Logging
- `Log::info` por cada cliente archivado individualmente
- `Log::info` con total de clientes archivados al final
- `Log::error` si falla soft-delete de un cliente

### Excepciones
- `\Throwable` capturado por cada `$cliente->delete()` — no interrumpe el batch

### Queries
- `Cliente::whereNull('deleted_at')->whereDoesntHave('operaciones', fn($q) => $q->where('fecha', '>=', $fechaLimite))->get()`
- `$cliente->delete()` (soft delete — UPDATE `deleted_at`)

---

## `ProcesarFifoOperacionJob`
- **File**: `api/app/Jobs/ProcesarFifoOperacionJob.php`
- **Schedule**: No programado — se despacha manualmente desde código (probablemente tras crear una operación)
- **Tries**: default (1)
- **Timeout**: default
- **Queue**: default

### Lógica
- **Stub / placeholder** — el cuerpo del `handle()` contiene solo comentarios:
  - `// TODO Fase 4: FifoService::procesarOperacion($this->operacionId)`
  - `// TODO Fase 4: lotes_fifo usa titular_id, no cuenta_id`

### Constructor
- `__construct(public readonly int $operacionId)` — recibe el ID de la operación a procesar

### Modelos/Servicios
- Ninguno usado actualmente (TODO)
- Nota: `LotesFIFO` por `(titular_id, moneda_id)`, no por `cuenta_id`

### Logging
- Ninguno

### Excepciones
- Ninguna

### Estado actual
- **No implementado** — es un placeholder para Fase 4 del proyecto

---

## `RecalcularSaldoCuentaJob`
- **File**: `api/app/Jobs/RecalcularSaldoCuentaJob.php`
- **Schedule**: No programado — se despacha manualmente
- **Tries**: default (1)
- **Timeout**: default
- **Queue**: default

### Lógica
- **Stub / placeholder** — el cuerpo del `handle()` contiene solo comentario:
  - `// TODO Fase 3: sumar todos los movimientos de cada cuenta y actualizar saldo_cache + saldo_cache_at`

### Constructor
- `__construct(public readonly array $cuentaIds)` — recibe array de IDs de cuentas contables

### Modelos/Servicios
- Ninguno usado actualmente (TODO)

### Logging
- Ninguno

### Excepciones
- Ninguna

### Estado actual
- **No implementado** — es un placeholder para Fase 3 del proyecto

---

## `VerificarSlaPoolJob`
- **File**: `api/app/Jobs/VerificarSlaPoolJob.php`
- **Schedule**: Cada minuto, `->withoutOverlapping()`, name `verificar-sla-pool`
- **Tries**: default (1)
- **Timeout**: default
- **Queue**: default
- **Dispatcher**: `Schedule::job(new VerificarSlaPoolJob())->everyMinute()->withoutOverlapping()` desde `routes/console.php`

### Lógica
1. `$threshold = now()->subMinutes(5)` — operaciones con más de 5 min de espera
2. Query:
   ```php
   Operacion::where('estado_pool', 'pendiente')
       ->whereNull('sla_notificado_en')
       ->where('created_at', '<=', $threshold)
       ->get()
   ```
   - Solo operaciones **realmente pendientes** en el pool (`estado_pool = 'pendiente'`)
   - Excluye las que ya fueron notificadas (`sla_notificado_en IS NULL`)
   - Excluye las que no superan el umbral de 5 minutos
3. Si no hay operaciones → `return` (sin logging)
4. Por cada operación:
   - Calcula `$minutosEspera` (casteado a `int`)
   - `PoolNotifier::slaExcedida()` — log de advertencia
   - `event(new SlaExcedida(...))` — broadcast Reverb al canal `pool` con nombre `sla.excedida`
   - `$operacion->update(['sla_notificado_en' => now()])` — marca para no repetir la alarma
   - `Log::warning` con detalle de la operación

### Componentes involucrados
- `App\Models\Operacion` — consulta operaciones en pool pendiente
- `App\Services\Pool\PoolNotifier` — log de SLA excedido
- `App\Events\SlaExcedida` — broadcast event (canal `pool`, nombre `sla.excedida`)
- Reverb (WebSocket server) — recibe broadcast y lo envía a clientes Echo

### Broadcast (SlaExcedida)
- **Canal**: `pool` (público, sin autenticación)
- **Nombre**: `sla.excedida`
- **Payload**: `{ operacion_id, minutos_espera, created_at }`
- **Driver**: Reverb (vía Pusher protocol), escucha en `reverb:8080` (interno Docker) / `localhost:8080` (frontend)
- **Frontend**: Echo escucha `channel('pool').listen('.sla.excedida', ...)` en `AppShell.vue`, dispara evento `window` `sla-excedida` que `PoolAlarm.vue` captura para mostrar modal + sonido

### Dependencias infraestructura
- **Reverb** debe estar corriendo (servicio `reverb` en docker-compose, puerto 8080)
- **Horizon** debe procesar la cola `default` (servicio `horizon` en docker-compose)
- **Redis** como backend de cola y caché

### Consideraciones
- Usa `Queueable` trait (requerido por `Schedule::job()` para `onConnection()`/`onQueue()`)
- `sla_notificado_en` está en `$fillable` del modelo y casteado como `datetime`
- La alarma suena **1 vez por operación** gracias al filtro `whereNull('sla_notificado_en')`
- La migración `add_sla_notificado_en_to_operaciones_table` agrega la columna

### Logging
- `Log::warning` por cada operación que excede SLA

### Excepciones
- Ninguna capturada explícitamente — si falla broadcast, el job se marca como `failed` en Horizon
