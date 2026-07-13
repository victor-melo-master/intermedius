# Intermedius Frontend — Contexto Completo para IA

> SPA en Vue 3. Documentación jerárquica. Cada subdirectorio tiene su propio `AI_CONTEXT.md`.

---

## 1. Stack

| Componente | Tecnología |
|---|---|
| Framework | Vue 3 (Composition API, `<script setup>`) |
| Estado | Pinia (9 stores) |
| Router | Vue Router 4 (19 rutas, guard beforeEach) |
| HTTP | Axios (interceptor de token) |
| CSS | Tailwind CSS (utility-first) |
| Build | Vite |

---

## 2. Estructura

```
frontend/
├── index.html
├── vite.config.js
├── tailwind.config.js
├── postcss.config.js
├── package.json
├── .env
└── src/
    ├── main.js                    → Entry point (createApp, use pinia, use router)
    ├── App.vue                    → Componente raíz
    ├── index.css                  → Tailwind directives + estilos globales
    ├── api/
    │   └── axios.js               → Instancia axios con interceptors
    ├── router/
    │   └── index.js               → 19 rutas + beforeEach guard
    ├── stores/                    → 9 Pinia stores
    ├── components/                → 11 componentes reutilizables
    └── views/                     → 16 vistas/páginas
```

---

## 3. Stores (Pinia)

Ver `frontend/src/stores/AI_CONTEXT.md` para detalle completo.

| Store | Archivo | Estado principal | Métodos clave |
|---|---|---|---|
| `useAuthStore` | `auth.js` | token, user | login(), logout(), checkRole() |
| `useBancosStore` | `bancos.js` | list, loading | fetchAll(), create() |
| `useClientesStore` | `clientes.js` | list, loading | fetchAll(), fetchTrashed(), create(), update(), restore() |
| `useOperacionesStore` | `operaciones.js` | list, loading | fetchAll(), create(), update(), fetchDetail(), verificar() |
| `usePoolStore` | `pool.js` | list, misOrdenes | fetchAll(), fetchMisOrdenes(), tomar(), soltar(), pagar(), cancelar() |
| `useTasasStore` | `tasas.js` | actuales, historial, monedas | fetchActuales(), fetchHistorial(), publicar() |
| `useTasasReferenciaStore` | `tasasReferencia.js` | list | fetchAll() |
| `useTitularesStore` | `titulares.js` | list, loading | fetchAll(), create(), update() |
| `useUsuariosStore` | `usuarios.js` | list, loading | fetchAll(), create(), update(), toggleActivo() |

---

## 4. Rutas

Ver `frontend/src/router/AI_CONTEXT.md` para detalle completo.

| Ruta | Vista | Sidebar |
|---|---|---|
| `/login` | LoginView | No |
| `/email/verify` | EmailVerifyView | No |
| `/operaciones` | OperacionesView | Sí |
| `/operaciones/nueva` | OperacionFormView | Sí |
| `/operaciones/nueva/intermediada` | OperacionIntermediadaForm | Sí |
| `/operaciones/:id` | OperacionDetailView | Sí |
| `/operaciones/moneda/:moneda` | OperacionMonedaView | Sí |
| `/cuentas` | CuentasView | Sí |
| `/clientes` | ClientesView | Sí |
| `/titulares` | TitularesView | Sí |
| `/bancos` | BancosView | Sí |
| `/tasas` | TasasView | Sí |
| `/dashboard` | DashboardView | Sí |
| `/pool` | PoolView | Sí |
| `/reportes` | ReportesView | Sí |
| `/comisiones` | ComisionesView | Sí |
| `/usuarios` | UsuariosView | Sí |

---

## 5. Componentes

Ver `frontend/src/components/AI_CONTEXT.md` para detalle completo.

| Componente | Propósito |
|---|---|
| `AppShell` | Layout con sidebar/navbar, logout, nav items |
| `AppPageHeader` | Encabezado con título + botón de acción |
| `AppLoadingSpinner` | Indicador de carga |
| `AppErrorState` | Estado de error con retry |
| `AppEmptyState` | Estado vacío con icono |
| `ClienteSelector` | Búsqueda async de clientes con debounce |
| `CuentaSelector` | Selector de cuenta filtrado por moneda |
| `TipoOperacionSelector` | Selector de tipo de operación |
| `CalculadoraBidireccional` | Calculadora de tasas (origen/destino) |
| `ComisionToggle` | Toggle + input de comisión en formulario |
| `ResumenOperacion` | Resumen pre-confirmación |

---

## 6. Convenciones de código

### Naming
- Archivos: PascalCase para `.vue`, camelCase para `.js`
- Stores: `use{Name}Store` + `defineStore('name', fn)`
- Componentes: PascalCase, single-file components con `<script setup>`
- Eventos: kebab-case en template, camelCase en emit

### API calls
- Instancia única en `src/api/axios.js` con `baseURL` desde env
- Token en localStorage, inyectado via interceptor
- 401 → redirect a login automático
- Todas las respuestas se envuelven en try/catch en la vista

### Formateo
- Moneda: `new Intl.NumberFormat('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n)`
- Fechas: `new Date(fecha).toLocaleDateString('es-VE', { day: '2-digit', month: '2-digit', year: 'numeric' })`

### Modales
- Modal pattern: `fixed inset-0 z-50 flex items-end sm:items-center justify-center`
- Backdrop: `absolute inset-0 bg-black/40` con `@click.self="close"`
- Scroll: `max-h-[90vh] overflow-y-auto`
- Responsive: `rounded-t-2xl sm:rounded-2xl` (bottom sheet en mobile, centered en desktop)

### Roles (desde backend)
- `super_admin`: acceso total
- `admin`: CRUD completo, verificar, pool, config
- `operador`: crear operaciones, ver catálogos
- `contador`: reportes, gastos
- `lectura`: solo ver
- `pagador`: pool (tomar, soltar, pagar)

En frontend se usa `auth.isAdmin` (computed que verifica `roles.includes('admin') || roles.includes('super_admin')`)

### UI/UX
- Botones peligrosos (eliminar): fondo rojo (`bg-red-600`)
- Botones de acción primaria: fondo azul (`bg-blue-600`)
- Estados de carga: `AppLoadingSpinner`
- Estados de error: `AppErrorState` con botón retry
- Estados vacíos: `AppEmptyState` con emoji + mensaje
- Confirmaciones: `confirm()` nativo de JS para acciones destructivas

### Soft Delete (clientes)
- Toggle "Activos / Papelera" en header
- `mostrarPapelera` ref → llama `fetchAll` o `fetchTrashed`
- Lista: clients con `deleted_at` tienen clase `opacity-70 border-red-200`
- Botón "Recuperar" en lista + modal detalle
- Botón "Eliminar" en modal detalle con confirmación

### Verificación de Email (EmailVerifyView)
- Ruta pública: `/email/verify?email=X&hash=Y`
- Lee `email` y `hash` de query params
- Llama `POST /api/v1/auth/verificar-email` con `{email, hash}`
- Muestra estado: cargando → éxito → error
- Flujo: usuario recibe email con link → hace clic → se verifica automáticamente
- Si hash inválido o usuario no existe, muestra error con opción de reenviar

### Cuentas tipo efectivo
- Selector de tipo al inicio del formulario
- `v-if="form.tipo !== 'efectivo'"` en banco y número de cuenta
- Submit omite banco_id y numero_cuenta si tipo es efectivo

---

## 7. Archivos por directorio

| Directorio | Archivo AI_CONTEXT |
|---|---|
| `frontend/` | `frontend/AI_CONTEXT.md` (este archivo) |
| `frontend/src/` | `frontend/src/AI_CONTEXT.md` |
| `frontend/src/stores/` | `frontend/src/stores/AI_CONTEXT.md` |
| `frontend/src/components/` | `frontend/src/components/AI_CONTEXT.md` |
| `frontend/src/views/` | `frontend/src/views/AI_CONTEXT.md` |
| `frontend/src/router/` | `frontend/src/router/AI_CONTEXT.md` |
| `frontend/src/api/` | `frontend/src/api/AI_CONTEXT.md` |
