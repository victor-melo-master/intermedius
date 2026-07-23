# AGENTS — Intermedius

## Contexto del Proyecto

### Flujo Multi-Paso (Operaciones con Transacciones)

**Rama:** `feat/flujo-multi-paso`  
**Branch base:** `origin/main`

Arquitectura de operaciones en 4 pasos en lugar del CRUD directo de movimientos:

```
solicitud ──[iniciar]──→ en_progreso ──[cerrar]──→ cerrada
     │                      │
     └──[cancelar]──────────┴──[cancelar]──→ cancelada
```

**Backend:**
- `RegistroOperacionService::crearSolicitud()` — crea sin movimientos, guarda `moneda_operacion_id`
- `RegistroOperacionService::iniciarOperacion()` — `solicitud → en_progreso`
- `RegistroOperacionService::cerrarOperacion()` — delega en `CierreOperacionService`
- `RegistroOperacionService::cancelarOperacion()` — revierte saldos si hay confirmadas
- `RegistroOperacionService::crearVenta()` — crea operación venta + cierre atómico (estado `cerrada` directo)
- `RegistroOperacionService::revertirOperacion()` — reversión de ventas cerradas (máx 30 días, motivo requerido)
- `CierreOperacionService` — contiene: `validarComprobantes`, `validarBalance`, `generarMovimientos`, `calcularGanancia`, `cuentasAfectadas`
- `TransaccionController` — CRUD completo de transacciones: store, update, delete, confirmar, revertir, **fallar**
- `TransaccionService::fallarTransaccion()` — marca transacción pendiente como `fallido` con razón
- `SolicitudOperacionRequest` — requiere `moneda_codigo` (antes era implícito USD)
- `VentaOperacionRequest` — valida creación de venta: `moneda_codigo`, `tasa_aplicada`, `cliente_id`, `monto_solicitado`, `transacciones[]`
- `TransaccionResource` — expone `cuenta_origen.alias`, `cuenta_destino.alias`
- Límite de monto excluye transacciones `revertida`/`cancelada`
- PoolController: `index`/`mis-ordenes` filtran solo `compra_usd`; `tomar()` avanza a `en_progreso`

**Base de datos:**
- `operaciones.moneda_operacion_id` (FK → monedas.id, nullable) — qué moneda se negocia (USD/USDT/EUR/COP)
- `transacciones.cliente_id` (FK → clientes.id, nullable) — cliente de la transacción directa
- `clientes.datos_bancarios` (JSON, nullable) — datos bancarios del cliente
- `operaciones.revertida_at` (datetime, nullable) — marca de reversión de venta

**Frontend:**
- Vistas: `OperacionFormView` (nueva/editar), `GestionarOperacionView`, `OperacionDetailView`
- Componentes: `FlujoProgress`, `TransaccionList`, `TransaccionForm`, `ConfirmarTransaccionModal`, `CalculadoraBidireccional`
- Composables: `useOperacionForm`, `useTransacciones`, `useOperaciones` (solicitar/iniciar/cerrar/cancelar/fetchGananciaPreview)
- Store: `operaciones` (solicitar/iniciar/cerrar/cancelar/fetchGananciaPreview)
- `monedaEsUSD` → `esDivisa` (cualquier moneda no-VES funciona como divisa)
- Botón "Agregar transacción" se deshabilita cuando balanceado, muestra "✅ Transacciones balanceadas"
- Botón "Cerrar" deshabilitado hasta balancear, abre modal con campo tasa de mercado
- Preview de ganancia estimada en `GestionarOperacionView` (card + modal de cierre)
- Redirige a `/operaciones` al cerrar
- Fechas en formato `dd/mm/yyyy`
- Direccionalidad compra/venta: compra = casa compra divisa (cliente entrega divisa → casa entrega VES); venta = casa vende divisa (cliente entrega VES → casa entrega divisa)

### Cálculo de Ganancia

**Rama:** `feat/calculo-ganancia`

**Fórmulas (multi-divisa: USD/USDT/EUR/COP):**
- `venta_usd`: `ganancia_ves = monto_divisa × (tasa_aplicada − tasa_mercado)`, `ganancia_usd = ganancia_ves / tasa_aplicada`
- `compra_usd`: `ganancia_ves = monto_divisa × (tasa_mercado − tasa_aplicada)`, `ganancia_usd = ganancia_ves / tasa_mercado`
- `intermediada`: `ganancia_ves = montoDivisa × (tasa_venta − tasa_compra)`, `ganancia_usd = ganancia_ves / tasa_venta`
- `comision`: directa desde `monto_usd_equivalente`
- Netas: `bruta − comisiones` (CalculadorComisionesService)

**Snapshot al cierre:** `tasa_mercado_snapshot` se actualiza al momento de cerrar la operación (no al crear solicitud).

**Preview:** `GET /operaciones/{id}/ganancia-preview?tasa_mercado=X` retorna ganancia estimada sin persistir.

**`genera_ganancia`:** `venta_usd`, `compra_usd`, `intermediada`, `comision` = `true`. Resto = `false`.

## Rendimiento (diagnóstico Jul 2026)

### 🔴 Críticos (arreglar ya)
1. **Xdebug activo en prod** — `docker/php/php.ini:24` → `xdebug.mode = off`
2. **Opcache deshabilitado** — `docker/php/php.ini:13` → `opcache.enable = 1`
3. **Cache/Queue en DB, no Redis** — `.env:37,39` → `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`

### 🟡 Moderados
4. **N+1 queries en RegistroOperacionService** — `Cuenta::find()` por cada movimiento en líneas 64,107,139,179. Batch con `whereIn()`.
5. **Dashboard resumen sin paginación** — `DashboardController.php:143` usa `->get()`. Cambiar a `chunk()` o paginación.
6. **Pool eager load 11 relaciones** — Revisar si todas son necesarias en el listado.

### 🟢 Menores
7. **LIKE %search% en Clientes** — Usar FULLTEXT index existente.
8. **Sesiones en MySQL** — Migrar a Redis o file.
