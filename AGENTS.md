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
- `RegistroOperacionService::cerrarOperacion()` — genera movimientos desde transacciones confirmadas, valida balance
- `RegistroOperacionService::cancelarOperacion()` — revierte saldos si hay confirmadas
- `RegistroOperacionService::validarBalanceCierre()` — verifica suma divisa = monto_solicitado, suma VES = monto × tasa
- `TransaccionController` — CRUD completo de transacciones: store, update, delete, confirmar, revertir
- `SolicitudOperacionRequest` — requiere `moneda_codigo` (antes era implícito USD)
- `TransaccionResource` — expone `cuenta_origen.alias`, `cuenta_destino.alias`
- Límite de monto excluye transacciones `revertida`/`cancelada`

**Base de datos:**
- `operaciones.moneda_operacion_id` (FK → monedas.id, nullable) — qué moneda se negocia (USD/USDT/EUR/COP)

**Frontend:**
- Vistas: `OperacionFormView` (nueva/editar), `GestionarOperacionView`, `OperacionDetailView`
- Componentes: `FlujoProgress`, `TransaccionList`, `TransaccionForm`, `ConfirmarTransaccionModal`, `CalculadoraBidireccional`
- Composables: `useOperacionForm`, `useTransacciones`, `useOperaciones` (solicitar/iniciar/cerrar/cancelar)
- Store: `operaciones` (solicitar/iniciar/cerrar/cancelar)
- `monedaEsUSD` → `esDivisa` (cualquier moneda no-VES funciona como divisa)
- Botón "Agregar transacción" se deshabilita cuando balanceado, muestra "✅ Transacciones balanceadas"
- Botón "Cerrar" deshabilitado hasta balancear
- Redirige a `/operaciones` al cerrar
- Fechas en formato `dd/mm/yyyy`

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
