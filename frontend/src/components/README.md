# components/ — Componentes reutilizables

| Componente | Props | Emits | Propósito |
|---|---|---|---|
| `AppEmptyState` | icon, message, subtitle | — | Estado vacío con icono y mensaje |
| `AppErrorState` | message, retry | retry | Estado de error con botón reintentar |
| `AppLoadingSpinner` | — | — | Spinner de carga |
| `AppPageHeader` | title, actionLabel | action | Encabezado con título y botón de acción |
| `AppShell` | — | — | Layout con sidebar/navbar + router-view |
| `CalculadoraBidireccional` | 11 props (monedaOrigen, monedaDestino, tasa, etc.) | 3 emits | Calculadora de tasas bidireccional |
| `ClienteSelector` | modelValue, clienteTieneCuentas | update:modelValue, select | Selector de cliente con búsqueda async |
| `ComisionToggle` | 5 props | 3 emits | Toggle de comisión en operación |
| `CuentaSelector` | 7 props (monedaId, clienteId, etc.) | update:modelValue | Selector de cuenta filtrado por moneda |
| `ResumenOperacion` | items | — | Resumen de operación antes de confirmar |
| `TipoOperacionSelector` | 5 props | 2 emits | Selector de tipo de operación con opciones dinámicas |
