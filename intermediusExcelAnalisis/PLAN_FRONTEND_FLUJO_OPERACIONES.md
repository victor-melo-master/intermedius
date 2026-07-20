# Plan Frontend: Flujo Multi-Paso de Operaciones

## Estado actual

La API ya soporta el flujo completo:
```
solicitud → en_progreso → cerrada / cancelada
```
Con endpoints: `POST /solicitud`, `POST /iniciar`, `POST/PUT/DELETE /transacciones`, `PATCH /confirmar`, `PATCH /revertir`, `POST /cerrar`, `POST /cancelar`.

El frontend actualmente solo conoce el flujo legacy (estatus `sin_verificar → en_verificacion → verificado` con movimientos). No hay vistas ni composables para el nuevo flujo.

---

## Fase 1 — Actualizar capa de datos (composables + store)

### 1.1 Extender `useOperaciones` composable
**Archivo:** `src/composables/useOperaciones.js`

Agregar métodos:
- `solicitar(payload)` → `POST /operaciones/solicitud`
- `iniciar(id)` → `POST /operaciones/{id}/iniciar`
- `cerrar(id)` → `POST /operaciones/{id}/cerrar`
- `cancelar(id, motivo)` → `POST /operaciones/{id}/cancelar`

### 1.2 Crear composable `useTransacciones`
**Archivo nuevo:** `src/composables/useTransacciones.js`

Métodos:
- `agregar(operacionId, payload)` → `POST /operaciones/{id}/transacciones`
- `editar(operacionId, txId, payload)` → `PUT /operaciones/{id}/transacciones/{txId}`
- `confirmar(operacionId, txId)` → `PATCH /operaciones/{id}/transacciones/{txId}/confirmar`
- `revertir(operacionId, txId, motivo)` → `PATCH /operaciones/{id}/transacciones/{txId}/revertir`
- `eliminar(operacionId, txId)` → `DELETE /operaciones/{id}/transacciones/{txId}`
- `listar(operacionId)` → se obtiene del detail de la operación (`transacciones` array)

### 1.3 Extender store `operaciones.js`
**Archivo:** `src/stores/operaciones.js`

Agregar las mismas acciones que el composable pero en el store para compartir estado.

---

## Fase 2 — Vista principal `GestionarOperacionView`

### 2.1 Nueva ruta
**Archivo:** `src/router/index.js`

```
/operaciones/:id/gestionar → GestionarOperacionView.vue
```

### 2.2 Vista `GestionarOperacionView`
**Archivo nuevo:** `src/views/operaciones/GestionarOperacionView.vue`

Layout vertical con 3 secciones:

1. **Cabecera** — Resumen de la operación (tipo, fecha, cliente, tasas snapshot)
2. **Lista de transacciones** — Tabla con acciones por transacción
3. **Barra de progreso** — Muestra el estado actual (`solicitud` → `en_progreso` → `cerrada`)
4. **Botones de acción** — Según el estado:
   - `solicitud`: [Iniciar] [Cancelar]
   - `en_progreso`: [Agregar transacción] [Cerrar operación] [Cancelar]
   - `cerrada`: Solo lectura
   - `cancelada`: Solo lectura

### 2.3 Componente `FlujoProgress`
**Archivo nuevo:** `src/components/operaciones/FlujoProgress.vue`

Barra visual con pasos: Solicitud → En Progreso → Cerrada. Paso actual en azul, completados en verde, pendientes en gris.

---

## Fase 3 — Gestión de transacciones

### 3.1 Componente `TransaccionList`
**Archivo nuevo:** `src/components/operaciones/TransaccionList.vue`

Tabla de transacciones con columnas:
- Orden
- Cuenta origen → Cuenta destino
- Moneda + Monto
- Estado (badge: pendiente/amarilla, confirmada/verde, revertida/naranja)
- Método de pago
- Comprobante
- Acciones según estado:
  - `pendiente`: [Editar] [Confirmar] [Eliminar]
  - `confirmada`: [Revertir]

### 3.2 Componente `TransaccionForm`
**Archivo nuevo:** `src/components/operaciones/TransaccionForm.vue`

Formulario reutilizable (modal o inline) para crear/editar transacción:
- Selector cuenta origen (filtrada por moneda)
- Selector cuenta destino (filtrada por moneda)
- Moneda (auto-detectada de la cuenta origen)
- Monto
- Tasa aplicada (opcional)
- Método de pago (dropdown: efectivo, pago_movil, transferencia, etc.)
- Comprobante (texto, requerido si método ≠ efectivo)

### 3.3 Componente `ConfirmarTransaccionModal`
**Archivo nuevo:** `src/components/operaciones/ConfirmarTransaccionModal.vue`

Modal de confirmación antes de confirmar una transacción:
- Muestra resumen (cuenta origen, monto, destino)
- Si método ≠ efectivo, campo para comprobante
- Botón Confirmar / Cancelar

---

## Fase 4 — Modificar vistas existentes

### 4.1 `OperacionDetailView.vue`
**Archivo:** `src/views/operaciones/OperacionDetailView.vue`

Cambios:
- Si `op.estado === 'solicitud'` o `'en_progreso'`, mostrar botón "Gestionar transacciones" → navega a `/operaciones/:id/gestionar`
- Mostrar badge de `estado` (nuevo) además de `estatus` (legacy)
- Agregar badge de estado: solicitud (amarillo), en_progreso (azul), cerrada (verde), cancelada (rojo)

### 4.2 `OperacionesView.vue`
**Archivo:** `src/views/operaciones/OperacionesView.vue`

Cambios:
- Agregar filtro de `estado` (solicitud, en_progreso, cerrada, cancelada) en el modal de filtros
- Actualizar `estatusBadge()` para soportar el campo `estado` cuando existe
- Actualizar `montoUsd()` y `bolivares()` para soportar operaciones sin movimientos (usar transacciones)

### 4.3 `useOperacionForm.js`
**Archivo:** `src/composables/useOperacionForm.js`

Cambios en `submit()`:
- Si el flujo seleccionado es "multi-paso", llamar `solicitar()` en vez de `create()`
- Agregar opción de flujo en el formulario (radio: "Crear directa" vs "Crear solicitud")

---

## Fase 5 — Integración con Pool

### 5.1 `PoolView.vue`
**Archivo:** `src/views/pool/PoolView.vue`

Cambios:
- El pool ya excluye `estado=solicitud` en la API, no se necesita cambio de lógica
- Pero mostrar el `estado` de la operación en la tarjeta del pool (badge)
- Las operaciones con `en_progreso` deberían llegar al pool después de cerrar

---

## Fase 6 — Notificaciones y UX

### 6.1 Toast notifications
Usar `useNotification()` existente para feedback de cada acción:
- "Transacción confirmada exitosamente"
- "Transacción revertida"
- "Operación cerrada — movimientos generados"
- "Operación cancelada"

### 6.2 Estados de carga
Cada botón de acción debe mostrar spinner mientras procesa (patrón ya existente en PoolView con `acting` ref).

---

## Archivos a crear (resumen)

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `src/composables/useTransacciones.js` | Nuevo | CRUD de transacciones vía API |
| `src/views/operaciones/GestionarOperacionView.vue` | Nuevo | Vista principal del flujo multi-paso |
| `src/components/operaciones/FlujoProgress.vue` | Nuevo | Barra de progreso del flujo |
| `src/components/operaciones/TransaccionList.vue` | Nuevo | Lista de transacciones con acciones |
| `src/components/operaciones/TransaccionForm.vue` | Nuevo | Formulario crear/editar transacción |
| `src/components/operaciones/ConfirmarTransaccionModal.vue` | Nuevo | Modal de confirmación |

## Archivos a modificar (resumen)

| Archivo | Cambios |
|---------|---------|
| `src/composables/useOperaciones.js` | +4 métodos (solicitar, iniciar, cerrar, cancelar) |
| `src/stores/operaciones.js` | +4 acciones |
| `src/router/index.js` | +1 ruta (`/gestionar`) |
| `src/views/operaciones/OperacionDetailView.vue` | +botón gestionar, badge de estado |
| `src/views/operaciones/OperacionesView.vue` | +filtro estado, soporte sin movimientos |
| `src/composables/useOperacionForm.js` | +opción de flujo en submit |

## Orden de implementación

1. **Fase 1** — useTransacciones + extender useOperaciones (sin esto nada funciona)
2. **Fase 2** — GestionarOperacionView + FlujoProgress (vista base)
3. **Fase 3** — TransaccionList + TransaccionForm + ConfirmarModal (interacción con transacciones)
4. **Fase 4** — Modificar vistas existentes (detail + list + form)
5. **Fase 5** — Pool (solo badges)
6. **Fase 6** — UX polish

## Decisiones de diseño

- **Reutilizar patrón existente:** Todo con composable `useApi` + `<script setup>` + Tailwind, igual que el resto del codebase
- **No crear store nuevo:** Las transacciones se manejan como propiedad del detail de la operación (ya viene en `op.transacciones`)
- **Flujo dual:** La vista de detalle detecta `op.estado` vs `op.estatus` y muestra la UI apropiada
- **Una sola vista de gestión:** `GestionarOperacionView` maneja todos los pasos del flujo con secciones condicionales según `op.estado`
