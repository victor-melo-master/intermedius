# Contexto de desarrollo — Intermedius

## Seeders disponibles

| Seeder | Contenido | Ejecución |
|--------|-----------|-----------|
| `CatalogosBaseSeeder` | Monedas, roles, permisos, tipos de operación, categorías de gasto | `php artisan db:seed --class=CatalogosBaseSeeder` |
| `AdminUserSeeder` | Admin `admin@test.com` (pass aleatorio visible en consola) | Se ejecuta automáticamente con `db:seed` |
| `DesarrolloSedeer` | Bancos (10 VE + 3 US), titular/cliente Intermedius, ~10 cuentas con saldos, 6 tasas diarias, 2 clientes de prueba, usuario `intermedius@test.com` / `password123` (super_admin) | `php artisan db:seed --class=DesarrolloSedeer` |

## Ejecución completa

```bash
docker compose exec api php artisan db:seed
```

## Schedule (tareas programadas)

| Nombre | Job | Frecuencia |
|--------|-----|------------|
| `sincronizar-tasas` | `SincronizarTasasJob` | Cada minuto |
| `sincronizar-tasas-referencia` | `SincronizarTasasReferenciaJob` | Cada minuto |
| `verificar-sla-pool` | `VerificarSlaPoolJob` | Cada minuto |
| `alertar-tasas-faltantes-manana` | `AlertarTasasFaltantesJob` | Diario 08:00 |
| `alertar-tasas-faltantes-tarde` | `AlertarTasasFaltantesJob` | Diario 14:00 |
| `reporte-mensual-comisiones` | `GenerarReporteMensualComisionesJob` | Mensual día 1 06:00 |
| `auto-archivar-clientes-inactivos` | `AutoArchivarClientesInactivos` | Semanal domingo 03:00 |

Todas definidas en `routes/console.php`. Ejecutadas por el container `schedule` (`php artisan schedule:work`).

## SLA del Pool

`VerificarSlaPoolJob` corre cada minuto, busca operaciones con `estado_pool = 'pendiente'` y `created_at` > 5 min, y emite un broadcast Reverb al canal `pool` con nombre `sla.excedida`. La alarma suena **una vez por operación** (columna `sla_notificado_en` evita duplicados). Requiere Reverb corriendo (container `reverb`, puerto 8080) y Horizon procesando la cola.

## Cola y WebSockets

- **Queue driver**: Redis (vía Horizon)
- **Broadcast driver**: Reverb (Pusher protocol)
- **Revers**: container `reverb:8080` en docker-compose
- **Horizon**: container `horizon` (`php artisan horizon`)
- **Conexión interna**: API/Horizon/Schedule conectan a `reverb:8080` (nombre del container en Docker network)

## CierreOperacionService

Extraído de `RegistroOperacionService` en Jul 2026. Centraliza la lógica de cierre de operaciones:

- **`validarComprobantes(transacciones)`** — exige comprobante si método pago ≠ efectivo
- **`validarBalance(operacion, transacciones)`** — solo considera transacciones `confirmada`
- **`generarMovimientos(operacion, transacciones)`** — crea movimientos contables desde transacciones
- **`calcularGanancia(operacion)`** — spread entre tasa aplicada y tasa de mercado snapshot
- **`cuentasAfectadas(transacciones)`** — extrae IDs únicos de cuentas origen/destino

## Migraciones recientes (Jul 2026)

| Archivo | Tabla | Cambio |
|---|---|---|
| `2026_07_23_000001_...` | `transacciones` | ADD `cliente_id` nullable |
| `2026_07_23_000002_...` | `clientes` | ADD `datos_bancarios` JSON |
| `2026_07_23_000003_...` | `operaciones` | ADD `revertida_at` datetime nullable |

## Novedades en rutas (Jul 2026)

| Método | Ruta | Controlador |
|---|---|---|
| POST | `/api/v1/operaciones/venta` | `OperacionController::venta()` |
| POST | `/api/v1/operaciones/{operacion}/revertir` | `OperacionController::revertir()` |
| PATCH | `/api/v1/operaciones/{operacion}/transacciones/{transaccion}/fallar` | `TransaccionController::fallar()` |

## Frontend — Estructura de transacciones

### Payload que envía `submit()` al backend (`POST /api/v1/operaciones`)

```json
{
  "fecha": "YYYY-MM-DD",
  "tipo_codigo": "venta_usd|compra_usd",
  "cliente_id": 5,
  "operador_id": 1,
  "tasa_aplicada": 40.50,
  "descripcion": "...",
  "movimientos": [
    {
      "cuenta_id": 10,
      "monto": -100.00
    },
    {
      "cuenta_id": 20,
      "monto": 4050.00
    }
  ],
  "genera_comision": true,
  "monto_comision": 12.15
}
```

**Importante**: el backend espera `movimientos[]` con `cuenta_id` y `monto` (negativo = débito, positivo = crédito). NO usa `transacciones[]`. Las comisiones son por fila (tipo selector en `TransaccionRow.vue`) y se suman a nivel operación.

### Componentes clave

| Archivo | Propósito |
|---------|-----------|
| `frontend/src/views/OperacionFormView.vue` | Formulario de operaciones con transacciones dinámicas |
| `frontend/src/components/operaciones/TransaccionRow.vue` | Fila individual (moneda, salida/entrada, monto, comisión por fila) |
| `frontend/src/components/CalculadoraBidireccional.vue` | Calculadora monto/tasa/bolívares con 2 modos |
| `frontend/src/components/AppShell.vue` | Layout global — contiene listener Echo + `<PoolAlarm />` |
| `frontend/src/components/pool/PoolAlarm.vue` | Modal + sonido de SLA |

### SLA Alarm frontend

Flujo:
1. `AppShell.vue` (siempre montado) escucha `channel('pool').listen('.sla.excedida', ...)`
2. Dispara `window.dispatchEvent(new CustomEvent('sla-excedida', { detail }))`
3. `PoolAlarm.vue` escucha el evento `sla-excedida` en `window`
4. Muestra modal con datos de la operación + reproduce sonido (`/sounds/alarma.mp3`)

### Reglas de negocio

- Los movimientos se envían como `movimientos[]` con `cuenta_id` + `monto` signed
- Las comisiones son por transacción (fila), no a nivel operación
- El backend rechaza edición si estado !== 'en_espera'
- Después de crear, se redirige a `/pool`
