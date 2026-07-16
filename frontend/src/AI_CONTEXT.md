# src/ — AI Context

> Punto de entrada y estructura completa del frontend SPA de Intermedius.

## Estructura

```
src/
├── main.js                 → Entry point: createApp, Pinia, Router, errorHandler, mount #app
├── App.vue                 → Componente raíz (solo <router-view />)
├── index.css               → Tailwind directives (@tailwind base/components/utilities)
├── errorHandler.js         → Global error handler para Vue app
│
├── api/
│   └── axios.js            → Instancia Axios con interceptors (token, 401 redirect)
│
├── router/
│   └── index.js            → 19 rutas + beforeEach guard (auth + role redirects)
│
├── plugins/
│   └── echo.js             → Laravel Echo + Pusher/Reverb (WebSocket broadcaster)
│
├── stores/                 → 9 Pinia stores (Composition API)
│   ├── auth.js             → Token, user, roles, login/logout
│   ├── operaciones.js      → CRUD operaciones + verificar
│   ├── pool.js             → Pool de pagadores + tomar/soltar/pagar/cancelar
│   ├── clientes.js         → CRUD clientes + soft-delete/restore
│   ├── tasas.js            → Tasas vigentes + historial + publicar
│   ├── tasasReferencia.js  → Tasas de referencia dashboard
│   ├── bancos.js           → CRUD bancos
│   ├── titulares.js        → CRUD titulares
│   └── usuarios.js         → CRUD usuarios + toggleActivo
│
├── composables/            → 10 composables reutilizables
│   ├── useApi.js           → Core: loading/error/data refs + AbortController
│   ├── useAuth.js          → Auth: login/logout/fetchMe con useApi
│   ├── useOperaciones.js   → Operaciones CRUD con useApi
│   ├── usePool.js          → Pool con computed filters + acciones
│   ├── useClientes.js      → Clientes CRUD + search + restore
│   ├── useCuentas.js       → Cuentas fetch + filtros por moneda/cliente/titular
│   ├── useTasas.js         → Tasas vigentes + historial + getTasaPar
│   ├── useBancos.js        → Bancos CRUD
│   ├── useTitulares.js     → Titulares fetch + getIntermedius
│   ├── useNotification.js  → Toast notifications (success/error/warning/info)
│   └── useInactivityTimer.js → Auto-logout por inactividad (30 min default)
│
├── components/             → 20 componentes Vue reutilizables
│   ├── common/             → AppPageHeader, AppEmptyState, AppLoadingSpinner, AppErrorState
│   ├── layout/             → AppShell (sidebar + navbar)
│   ├── clientes/           → ClienteSelector (búsqueda async)
│   ├── cuentas/            → CuentaSelector (filtrado por moneda)
│   ├── pool/               → PoolTimer, PoolList, PoolAlarm, PoolActions
│   ├── operaciones/        → CalculadoraBidireccional, ResumenOperacion, TransaccionRow
│   ├── operaciones/form/   → OperacionFormCabecera, OperacionFormTransacciones, OperacionFormComision, OperacionFormResumen
│   └── configuracion/      → TipoOperacionSelector, ComisionToggle
│
├── views/                  → 19 vistas/páginas
│   ├── auth/               → LoginView, EmailVerifyView
│   ├── operaciones/        → OperacionesView, OperacionFormView, OperacionIntermediadaForm, OperacionDetailView, OperacionMonedaView
│   ├── catalogos/          → ClientesView, CuentasView, TitularesView, BancosView, UsuariosView
│   ├── configuracion/      → TasasView, ComisionesView
│   ├── dashboard/          → DashboardView
│   ├── pool/               → PoolView
│   ├── reportes/           → ReportesView
│   └── NotFoundView.vue    → 404 page
│
└── utils/                  → (vacío — sin archivos)
```

## Stack

| Componente | Tecnología |
|---|---|
| Framework | Vue 3 (Composition API, `<script setup>`) |
| Estado | Pinia (9 stores, Composition API) |
| Router | Vue Router 4 (19 rutas, beforeEach guard) |
| HTTP | Axios (interceptor Bearer token) |
| WebSocket | Laravel Echo + Reverb/Pusher |
| CSS | Tailwind CSS (utility-first) |
| Build | Vite |
| Language | JavaScript (sin TypeScript) |

## Convenciones generales

- **Composition API** con `<script setup>` en todos los componentes
- **Props** con `defineProps`, **eventos** con `defineEmits`, **modelos** con `defineModel` o `v-model` explícito
- **Stores** siempre `use{Name}Store` con Composition API (`defineStore('name', () => { ... })`)
- **Composables** exportados como funciones nombradas `use{Name}`, base `useApi()` con AbortController
- **API calls** via `src/api/axios.js` (interceptor agrega token automáticamente)
- **Fechas** en español (`es-VE`), **moneda** en formato inglés con 2 decimales
- **Sin TypeScript** — todo JavaScript vanilla
- **Error handling**: todos los catch muestran `err.response?.data?.message || err.message`
- **Modales**: patrón `fixed inset-0 z-50 flex items-end sm:items-center justify-center` (bottom sheet en mobile)
- **Roles**: super_admin, admin, operador, contador, lectura, pagador

## Flujo de datos

```
main.js → createApp → use(Pinia) → use(Router) → mount('#app')
                            ↓
                    App.vue → <router-view />
                            ↓
                    AppShell.vue (layout) → sidebar + <router-view />
                            ↓
                    Views → use stores() → api/axios.js → Backend API
                            ↓
                    Components ← props/emits ← Views
```

## Archivos por directorio

| Directorio | AI_CONTEXT |
|---|---|
| `frontend/` | `frontend/AI_CONTEXT.md` |
| `frontend/src/` | `frontend/src/AI_CONTEXT.md` (este archivo) |
| `frontend/src/stores/` | `frontend/src/stores/AI_CONTEXT.md` |
| `frontend/src/components/` | `frontend/src/components/AI_CONTEXT.md` |
| `frontend/src/views/` | `frontend/src/views/AI_CONTEXT.md` |
| `frontend/src/router/` | `frontend/src/router/AI_CONTEXT.md` |
| `frontend/src/api/` | `frontend/src/api/AI_CONTEXT.md` |
| `frontend/src/composables/` | `frontend/src/composables/AI_CONTEXT.md` |
| `frontend/src/plugins/` | `frontend/src/plugins/AI_CONTEXT.md` |
