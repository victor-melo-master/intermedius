# components/ — AI Context

> 20 componentes Vue reutilizables organizados en 7 subdirectorios. Todos usan `<script setup>`.

## Estructura

```
components/
├── common/                         → Componentes de utilidad genérica
│   ├── AppPageHeader.vue           → Encabezado con título + botón de acción
│   ├── AppEmptyState.vue           → Estado vacío con icono + mensaje
│   ├── AppLoadingSpinner.vue       → Indicador de carga (spinner CSS)
│   └── AppErrorState.vue           → Estado de error con mensaje + botón retry
│
├── layout/
│   └── AppShell.vue                → Layout principal: sidebar + navbar + router-view
│
├── clientes/
│   └── ClienteSelector.vue         → Búsqueda async de clientes con debounce
│
├── cuentas/
│   └── CuentaSelector.vue          → Selector de cuenta filtrado por moneda
│
├── pool/                           → Componentes del pool de pagadores
│   ├── PoolTimer.vue               → Timer regresivo de expiración de orden
│   ├── PoolList.vue                → Lista de órdenes del pool
│   ├── PoolAlarm.vue               → Alerta sonora/visual para nuevas órdenes
│   └── PoolActions.vue             → Botones de acción (tomar, soltar, pagar)
│
├── operaciones/                    → Componentes de operaciones
│   ├── CalculadoraBidireccional.vue → Calculadora de tasas (divisa ↔ VES)
│   ├── ResumenOperacion.vue        → Resumen pre-confirmación
│   └── TransaccionRow.vue          → Fila de transacción (cuenta origen/destino, monto, comisión)
│
├── operaciones/form/               → Sub-componentes del formulario de operación
│   ├── OperacionFormCabecera.vue   → Cabecera: TipoOperacionSelector + ClienteSelector
│   ├── OperacionFormTransacciones.vue → Grid de TransaccionRow con acciones
│   ├── OperacionFormComision.vue   → Toggle + inputs de comisión
│   └── OperacionFormResumen.vue    → Resumen de items clave
│
└── configuracion/                  → Componentes de configuración
    ├── TipoOperacionSelector.vue   → Selector compra/venta + fecha
    └── ComisionToggle.vue          → Toggle + input de comisión (legacy)
```

---

## common/

### AppPageHeader
- **Propósito**: Encabezado de página con título y botón de acción
- **Props**: `title` (String), `actionLabel` (String)
- **Emits**: `action`
- **Uso**: Todas las vistas CRUD (ClientesView, BancosView, etc.)

### AppEmptyState
- **Propósito**: Muestra estado cuando no hay datos
- **Props**: `icon` (String, default '📄'), `message` (String), `subtitle` (String)
- **Uso**: Lista vacía en cualquier vista

### AppLoadingSpinner
- **Propósito**: Indicador de carga centrado
- **Props**: ninguna
- **Emits**: ninguno
- **Uso**: `v-if="loading"` en todas las vistas

### AppErrorState
- **Propósito**: Muestra error con opción de reintentar
- **Props**: `message` (String), `retry` (Boolean, default true)
- **Emits**: `retry`
- **Uso**: `v-else-if="error"` en todas las vistas

---

## layout/

### AppShell
- **Propósito**: Layout principal con sidebar de navegación y navbar
- **Props**: ninguna
- **Emits**: ninguno
- **Contenido**:
  - Sidebar con `baseNav` array: items de navegación con iconos (emojis), rutas, y roles permitidos
  - Filtro por roles: `auth.checkRole(item.roles)` oculta items según el rol del usuario
  - Navbar con nombre de usuario y botón logout
  - `<router-view />` para contenido principal
- **Nav items**: Operaciones, Cuentas, Clientes, Titulares, Bancos, Tasas, Dashboard, Pool, Reportes, Comisiones, Usuarios

---

## clientes/

### ClienteSelector
- **Propósito**: Búsqueda y selección de clientes con autocomplete
- **Props**: `modelValue` (Object — {id, nombre}), `clienteTieneCuentas` (Boolean)
- **Emits**: `update:modelValue`, `cuenta-agregada`
- **Comportamiento**:
  - Input con debounce (300ms) que busca en `GET /clientes?q=...`
  - Muestra resultados en dropdown
  - Si cliente no tiene cuentas, muestra botón "Agregar cuenta"
  - Botón "Limpiar" para deseleccionar

---

## cuentas/

### CuentaSelector
- **Propósito**: Selector de cuenta bancaria filtrado por moneda
- **Props**: `modelValue` (String/Number), `label` (String), `placeholder` (String), `cuentas` (Array), `emptyMessage` (String), `cuentaLabel` (Function), `bancos` (Array)
- **Emits**: `update:modelValue`
- **Comportamiento**: Muestra cuentas filtradas, formatea label con alias + banco + moneda

---

## pool/

### PoolTimer
- **Propósito**: Muestra tiempo restante antes de expirar una orden del pool
- **Props**: `expiresAt` (String — ISO date), `size` (String — 'sm' | 'md')
- **Computed**: `timeLeft` con días/horas/minutos/segundos, `isExpired`, `urgencyClass` (verde → amarillo → rojo)

### PoolList
- **Propósito**: Lista de órdenes del pool con tabs (Disponibles / Mis Órdenes)
- **Props**: `ordenes` (Array), `misOrdenes` (Array), `loading` (Boolean), `tab` (String)
- **Emits**: `select`, `tab-change`
- **Muestra**: ID, cliente, monto, moneda, tasa, timer de expiración

### PoolAlarm
- **Propósito**: Alerta sonora/visual cuando hay nuevas órdenes en el pool
- **Props**: `count` (Number), `enabled` (Boolean)
- **Emits**: `dismiss`
- **Comportamiento**: Beep sonoro + vibración en móvil

### PoolActions
- **Propósito**: Botones de acción para órdenes del pool
- **Props**: `orden` (Object), `isMisOrdenes` (Boolean)
- **Emits**: `tomar`, `soltar`, `pagar`, `cancelar`
- **Acciones**: Tomar orden, soltar orden, pagar (abre modal), cancelar (con motivo)

---

## operaciones/

### CalculadoraBidireccional
- **Propósito**: Calculadora de tasas bidireccional (divisa ↔ VES)
- **Props**: `monto` (String/Number), `bolivares` (String/Number), `tasa` (String/Number), `tipo` (String), `moneda` (String), `quoteCodigo` (String), `quoteSimbolo` (String), `quoteNombre` (String), `parStr` (String), `tasaSugerida` (Number/Null), `desfavorable` (Boolean)
- **Emits**: `update:monto`, `update:bolivares`, `update:tasa`
- **Modos**: 'divisa_ves' (calcula VES desde monto × tasa) o 'calcular_tasa' (calcula tasa desde VES/monto)
- **UI**: Dos modos intercambiables, indicador de tasa sugerida, warning si tasa desfavorable

### ResumenOperacion
- **Propósito**: Lista de items clave pre-confirmación
- **Props**: `items` (Array of {label, value})
- **Uso**: Muestra tipo, cliente, tasa, transacciones, comisión total

### TransaccionRow
- **Propósito**: Fila individual de transacción en el formulario
- **Props**: `index`, `monedas`, `cuentas`, `cuentaOrigenId`, `cuentaDestinoId`, `monedaId`, `monto`, `tipoOperacion`, `clienteId`, `intermediusTitularId`, `monedaForeignId`, `monedaQuoteId`, `comisionTipo`, `comisionMonto`
- **Emits**: `remove`, `update:cuentaOrigenId`, `update:cuentaDestinoId`, `update:monedaId`, `update:monto`, `update:comisionTipo`, `update:comisionMonto`
- **Lógica inteligente**: Filtra cuentas origen/destino según tipo (compra/venta) y moneda (foreign/quote), asigna cliente o titular según corresponda
- **Advertencia**: Muestra warning si monto > saldo disponible

---

## operaciones/form/

### OperacionFormCabecera
- **Propósito**: Cabecera del formulario — combina TipoOperacionSelector + ClienteSelector
- **Props**: `tipo`, `fecha`, `moneda`, `quoteSimbolo`, `today`, `clienteTieneCuentas`, `cliente`
- **Emits**: `update:tipo`, `update:fecha`, `update:cliente`, `cuenta-agregada`

### OperacionFormTransacciones
- **Propósito**: Grid de TransaccionRow con botones de acción
- **Props**: `transacciones`, `monedas`, `cuentas`, `loading`, `montoUSD`, `montoVES`, `resumen`, `tipoOperacion`, `clienteId`, `intermediusTitularId`, `monedaForeignId`, `monedaQuoteId`
- **Emits**: `agregar`, `eliminar`, `distribuir`, `limpiar`
- **Acciones**: Agregar fila, distribuir montos (divide equitativamente), limpiar filas
- **Validación**: Muestra resumen con ok/warning por moneda

### OperacionFormComision
- **Propósito**: Toggle + inputs de comisión en formulario
- **Props**: `activa` (Boolean), `tipo` (String), `monto` (String/Number), `simbolo` (String)
- **Emits**: `update:activa`, `update:tipo`, `update:monto`
- **Tipos**: pago_movil, transferencia, efectivo, otro

### OperacionFormResumen
- **Propósito**: Muestra resumen de items clave del formulario
- **Props**: `items` (Array of {label, value})

---

## configuracion/

### TipoOperacionSelector
- **Propósito**: Selector de tipo de operación (compra/venta) + fecha
- **Props**: `tipo` (String), `fecha` (String), `moneda` (String), `quoteSimbolo` (String), `today` (String)
- **Emits**: `update:tipo`, `update:fecha`
- **UI**: Dos botones grandes (compra/venta) con descripción + input date

### ComisionToggle
- **Propósito**: Toggle + input de comisión (versión legacy, ver OperacionFormComision)
- **Props**: `activa` (Boolean), `simbolo` (String)
- **Emits**: `update:activa`
