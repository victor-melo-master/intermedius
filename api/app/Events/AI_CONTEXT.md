# Events — AI Context

---

## `SlaExcedida`

- **File**: `api/app/Events/SlaExcedida.php`
- **Namespace**: `App\Events`
- **Propósito**: Notificar al frontend (vía WebSocket) que una operación del pool superó el SLA de 5 minutos.
- **Implementa**: `ShouldBroadcast` (broadcast cola Redis → Reverb → Echo)
- **Canal**: `pool` (público, sin autenticación)
- **Nombre del evento**: `sla.excedida`
- **Payload**:
  - `operacion_id` (int) — ID de la operación
  - `minutos_espera` (int) — Minutos transcurridos desde creación
  - `created_at` (string ISO8601) — Timestamp de creación de la operación

### Broadcast chain
1. `VerificarSlaPoolJob` hace `event(new SlaExcedida(...))`
2. El framework encola un `BroadcastEvent` en Redis (cola `default`)
3. Horizon procesa el `BroadcastEvent`
4. El driver Pusher envía HTTP POST a Reverb (`reverb:8080`)
5. Reverb envía el evento a todos los clientes Echo conectados al canal `pool`
6. El frontend (`AppShell.vue`) recibe el evento y dispara `window` custom event `sla-excedida`
7. `PoolAlarm.vue` muestra modal + reproduce sonido

### Dependencias
- Reverb corriendo (servicio `reverb` en docker-compose)
- Horizon procesando cola `default`
- Broadcasting driver: `reverb`
- Echo configurado en frontend con `VITE_REVERB_HOST=localhost:8080`

### Observaciones
- El constructor recibe `int $minutosEspera` — el caller debe castear a `int` si `diffInMinutes()` devuelve `float`
- `broadcastWith()` incluye datos mínimos para que el frontend muestre la alerta sin consultar la API
