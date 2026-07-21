# Flujo Multi-Paso — Frontend

## State Machine

```
solicitud ──[iniciar]──→ en_progreso ──[cerrar]──→ cerrada
     │                      │
     └──[cancelar]──────────┴──[cancelar]──→ cancelada
```

## Vistas

### `OperacionFormView.vue` (`/operaciones/nueva/:moneda`)
Formulario de creación/edición de solicitudes.

- URL param `:moneda` define la moneda a operar (USD, USDT, EUR, COP)
- Botón "Crear solicitud" → `POST /api/v1/operaciones/solicitud`
- Redirige a `/operaciones/{id}/gestionar` tras crear
- El nombre del tipo se adapta: "La casa compra/vende {moneda}"

### `GestionarOperacionView.vue` (`/operaciones/{id}/gestionar`)
Centro de operaciones para el flujo multi-paso.

**Secciones:**
1. Resumen de montos (divisa, tasa, bolívares)
2. Cuadro "El cliente entrega / La casa entrega"
3. Cabecera con estado, tipo, cliente
4. `FlujoProgress` — barra de progreso visual
5. `TransaccionList` — listado de transacciones con acciones
6. Botones de acción:
   - `solicitud`: "Iniciar operación"
   - `en_progreso`:
     - "Agregar transacción" (deshabilitado si balanceado)
     - "Cerrar operación" (solo si balanceado)
   - `en_progreso` / `solicitud`: "Cancelar operación"

**Estados del botón "Agregar transacción":**
- No balanceado: activo, permite agregar más transacciones
- Balanceado: se reemplaza por "✅ Transacciones balanceadas" (texto verde)

**Estados del botón "Cerrar":**
- No balanceado: deshabilitado, muestra "Confirma todas las transacciones para cerrar la operación"
- Balanceado: activo, redirige a `/operaciones` al cerrar

### `OperacionDetailView.vue` (`/operaciones/{id}`)
Vista de detalle legacy + multi-paso.

- Muestra el nombre con la moneda real (ej: "Compra de USDT")
- Muestra movimientos pareados (salida → entrada)
- Botón "Gestionar transacciones" para estado `solicitud`/`en_progreso`

### `OperacionesView.vue` (`/operaciones`)
Listado general de operaciones.

- Filtros por fecha, estado, tipo
- Badge de estado adaptado para multi-paso (`solicitud`, `en_progreso`, `cerrada`, `cancelada`)
- Fechas en formato `dd/mm/yyyy`

## Componentes

### `FlujoProgress.vue`
Barra de progreso visual con 3 pasos: Solicitud → En Progreso → Cerrada (o Cancelada).

Props: `estado`

### `TransaccionList.vue`
Lista de transacciones de la operación.

Props: `transacciones`, `operacionId`, `estado`

Muestra por cada transacción:
- Orden (#1, #2...)
- Estado (badge)
- Cuenta origen (alias)
- Cuenta destino (alias)
- Monto + moneda
- Método de pago
- Comprobante (si aplica)

Acciones según estado:
- `pendiente`: Editar, Confirmar, Eliminar
- `confirmada`: Revertir

### `TransaccionForm.vue`
Formulario para crear/editar transacciones.

Props: `operacionId`, `clienteId`, `clienteNombre`, `intermediusTitularId`, `monedasPermitidas`, `esCompra`, `tasaOperacion`, `montoSolicitado`, `transaccionesExistentes`

**Direccionalidad de cuentas:**
- `esDivisa` (cualquier moneda ≠ VES): funciona como USD en la lógica de cuentas
- Semántica: "compra" = la casa compra divisa del cliente; "venta" = la casa vende divisa al cliente
- Compra + Divisa: origen=Cliente, destino=Intermedius (cliente entrega divisa)
- Compra + VES: origen=Intermedius, destino=Cliente (casa entrega VES)
- Venta + Divisa: origen=Intermedius, destino=Cliente (casa entrega divisa)
- Venta + VES: origen=Cliente, destino=Intermedius (cliente entrega VES)

**Validación de límite:**
- `totalExistente` filtra solo `pendiente`/`confirmada`
- No cuenta transacciones `revertida`/`cancelada`
- Límite divisa = `monto_solicitado`
- Límite VES = `monto_solicitado × tasa_aplicada`

### `CalculadoraBidireccional.vue`
Calculadora interactiva: monto, bolívares, tasa — cualquier par calcula el tercero.

### `ConfirmarTransaccionModal.vue`
Modal de confirmación de transacción.

## Composables

### `useOperacionForm.js`
Lógica del formulario de creación/edición de solicitudes.

- `monedaSel` — desde `route.params.moneda`
- `submit()` — envía `moneda_codigo` + `tipo_codigo` + `monto_solicitado` etc.
- `cargarOperacion()` — carga datos existentes para edición
- `tipoCodigo` — `venta_usd` o `compra_usd` (no cambia por moneda)

### `useTransacciones.js`
CRUD de transacciones: `agregar()`, `confirmar()`, `revertir()`, `eliminar()`

### `useOperaciones.js` (composable)
Funciones: `solicitar()`, `iniciar()`, `cerrar()`, `cancelar()`

## Store

### `stores/operaciones.js`
Actions: `solicitar(body)`, `iniciar(id)`, `cerrar(id)`, `cancelar(id, motivo)`

## Convenciones

- `esDivisa` — cualquier moneda que no sea VES se comporta como divisa
- `esCompra` — `tipo_operacion.codigo === 'compra_usd'`
- `nombreOperacion` — reemplaza "USD" por la moneda real en el nombre del tipo
- `monedaDivisa` — desde `moneda_operacion.codigo`, fallback a 'USD'
- `monedasPermitidas` — desde `moneda_operacion`, filtro para cuentas
- Formato fecha: `dd/mm/yyyy` (locale `es-VE`)
