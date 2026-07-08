# components/ — AI Context

## `AppShell`
- Layout principal con sidebar y navbar
- Sidebar: logo, nav items (icono + nombre), logout
- Nav items: `baseNav` array con todas las rutas principales + `canPool` (condicional por rol)
- `drawer` ref: toggle sidebar en mobile
- `logout()`: llama auth.logout(), redirige a /login
- Estilo: sidebar fijo `w-64`, contenido `ml-64`, responsive con overlay en mobile

## `AppPageHeader`
- Props: `title` (string), `actionLabel` (string, vacío oculta botón)
- Emits: `action` (click en botón)
- Render: título a la izquierda, botón azul a la derecha

## `AppLoadingSpinner`
- Sin props. Render: spinner animado centrado con texto "Cargando..."
- Estilo: `w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin`

## `AppErrorState`
- Props: `message` (string), `retry` (boolean, default true)
- Emits: `retry`
- Render: icono ⚠️, mensaje de error, botón "Reintentar" (solo si retry=true)

## `AppEmptyState`
- Props: `icon` (string, default "📭"), `message` (string), `subtitle` (string opcional)
- Render: icono grande, mensaje, subtitle opcional

## `ClienteSelector`
- Model: `modelValue` (objeto cliente con id y nombre)
- Emits: `update:modelValue`, `select`
- Props: `clienteTieneCuentas` (boolean opcional)
- Lógica: búsqueda async con debounce 400ms via store `fetchAll()`
- UI: input de búsqueda + dropdown de resultados, tecla Escape cierra

## `CuentaSelector`
- Model: `modelValue` (id de cuenta)
- Props: `monedaId` (filtro opcional), `clienteId` (filtro opcional), `titularId`, `soloActivas`, `label`, `placeholder`
- Lógica: fetch cuentas, filtra por moneda/cliente/titular, computed `cuentasFiltradas`
- Muestra: alias + banco + moneda + saldo

## `TipoOperacionSelector`
- Model: `modelValue` (código del tipo)
- Props: `tipo`, `moneda`, `monto`, `label`
- Emits: `update:modelValue`, `select`
- Lógica: filtra tipos según parámetros de operación

## `CalculadoraBidireccional`
- Props: 11 props (monedaOrigen, monedaDestino, tasaCompra, tasaVenta, etc.)
- Emits: 3 emits (actualizar montos)
- Lógica: cálculo bidireccional (origen → destino y viceversa)

## `ComisionToggle`
- Props: 5 props (toggle state, monto, tipo comisión, moneda, readonly)
- Emits: 3 emits (toggle change, monto change)
- UI: toggle switch + input de monto condicional

## `ResumenOperacion`
- Props: `items` (array de objetos con label, value, highlight)
- Render: tarjeta con items en columna, valor destacado si highlight=true
