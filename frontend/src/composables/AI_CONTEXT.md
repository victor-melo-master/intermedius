# composables/ — AI Context

> 10 composables reutilizables con patrón base `useApi()` (AbortController + loading/error/data).

## Estructura

```
composables/
├── index.js                → Barrel export (re-exporta todos)
├── useApi.js               → Core: AbortController, loading, error, data refs
├── useAuth.js              → Login, logout, fetchMe, token persistence
├── useOperaciones.js       → Operaciones CRUD + verificar
├── usePool.js              → Pool con computed filters + acciones
├── useClientes.js          → Clientes CRUD + search + restore
├── useCuentas.js           → Cuentas fetch + filtros por moneda/cliente/titular
├── useTasas.js             → Tasas vigentes + historial + getTasaPar
├── useBancos.js            → Bancos CRUD
├── useTitulares.js         → Titulares fetch + getIntermedius
├── useNotification.js      → Toast notifications (success/error/warning/info)
└── useInactivityTimer.js   → Auto-logout por inactividad (30 min default)
```

---

## useApi.js — Core

Base para todos los composables. Provee `loading`, `error`, `data` refs y `execute()` con AbortController.

```js
const { loading, error, data, execute, abort } = useApi()

// Uso con función:
await execute((signal) => api.get('/endpoint', { signal }))

// Uso con config axios:
await execute({ method: 'get', url: '/endpoint' })
```

- **Abort automático**: cancela request anterior antes de iniciar uno nuevo
- **Cleanup**: abort en `onUnmounted`
- **Errores**: captura `AbortError` silenciosamente, demuestra `err.response?.data?.message`
- **Retorna**: `{ loading: Readonly<Ref>, error: Readonly<Ref>, data: Readonly<Ref>, execute: Function, abort: Function }`

---

## useAuth.js

```js
const { user, token, isAuthenticated, login, logout, fetchMe, loading, error } = useAuth()
```

- **login(credentials)**: `POST /auth/login` → guarda token + user en localStorage
- **logout()**: `POST /auth/logout` → limpia token + user
- **fetchMe()**: `GET /auth/me` → carga user actual
- **Auto-init**: si hay token en localStorage pero no user, llama `fetchMe()` automáticamente
- **isAuthenticated**: computed `!!token && !!user`

---

## useOperaciones.js

```js
const { list, detail, fetchAll, fetchOne, create, update, verificar, destroy, loading, error } = useOperaciones()
```

- **fetchAll(params)**: `GET /operaciones` con query params (tipo_codigo, estatus, fecha_desde, fecha_hasta, cliente_id, moneda)
- **fetchOne(id)**: `GET /operaciones/:id`
- **create(payload)**: `POST /operaciones`
- **update(id, payload)**: `PUT /operaciones/:id`
- **verificar(id)**: `PATCH /operaciones/:id/verificar`
- **destroy(id)**: `DELETE /operaciones/:id`

---

## usePool.js

```js
const { operaciones, enEspera, enProceso, concluidas, fetchAll, tomar, soltar, pagar, cancelar, loading, error } = usePool()
```

- **fetchAll()**: `GET /pool`
- **tomar(id)**: `POST /pool/:id/tomar` → refresca lista
- **soltar(id)**: `POST /pool/:id/soltar` → refresca lista
- **pagar(id)**: `POST /pool/:id/pagar` → refresca lista
- **cancelar(id, motivo)**: `POST /pool/:id/cancelar` → refresca lista
- **Computed**: `enEspera`, `enProceso`, `concluidas` (filtran por `estado`)

---

## useClientes.js

```js
const { list, detail, fetchAll, fetchOne, create, update, destroy, restore, search, loading, error } = useClientes()
```

- **fetchAll(params)**: `GET /clientes` con query params (search, inactivos, etc.)
- **fetchOne(id)**: `GET /clientes/:id`
- **create(payload)**: `POST /clientes`
- **update(id, payload)**: `PUT /clientes/:id`
- **destroy(id)**: `DELETE /clientes/:id`
- **restore(id)**: `PATCH /clientes/:id/restore`
- **search(query)**: `GET /clientes?search=query` → retorna array de resultados

---

## useCuentas.js

```js
const { cuentas, fetchAll, filtrarPorMoneda, filtrarPorCliente, filtrarPorTitular, getSaldo, loading, error } = useCuentas()
```

- **fetchAll(params)**: `GET /cuentas`
- **filtrarPorMoneda(monedaId)**: filtro local del array `cuentas`
- **filtrarPorCliente(clienteId)**: filtro local
- **filtrarPorTitular(titularId)**: filtro local
- **getSaldo(cuentaId)**: retorna `saldo_cache` de la cuenta

---

## useTasas.js

```js
const { vigentes, historico, fetchVigentes, fetchHistorico, getTasaPar, loading, error } = useTasas()
```

- **fetchVigentes()**: `GET /configuracion/tasas-vigentes`
- **fetchHistorico(params)**: `GET /configuracion/tasas-diarias/historico`
- **getTasaPar(baseCodigo, cotizadaCodigo)**: busca en `vigentes` por moneda_base.codigo y moneda_cotizada.codigo

---

## useBancos.js

```js
const { list, loading, error, fetchAll, create, update } = useBancos()
```

- **fetchAll()**: `GET /bancos`
- **create(body)**: `POST /bancos` → limpia list
- **update(id, body)**: `PUT /bancos/:id` → limpia list

---

## useTitulares.js

```js
const { list, loading, error, fetchAll, getIntermedius } = useTitulares()
```

- **fetchAll()**: `GET /titulares`
- **getIntermedius()**: retorna titular con `nombre === 'Intermedius'` del list (helper para formularios)

---

## useNotification.js

```js
const { notifications, show, hide, success, error, warning, info } = useNotification()
```

- **show(message, type, duration)**: crea toast con auto-dismiss (default 5s)
- **success/error/warning/info**: wrappers de `show` con tipo predefinido
- **notifications**: ref array compartido entre instancias (singleton global)
- **Animación**: `visible: true/false` para transiciones CSS, se remueve del array después de 300ms

---

## useInactivityTimer.js

```js
useInactivityTimer(30) // minutos
```

- **Auto-logout**: si no hay actividad (mousemove, keypress, click) por N minutos, ejecuta `auth.logout()` + redirect a `/login`
- **Lifecycle**: setup en `onMounted`, cleanup en `onUnmounted`
- **Default**: 30 minutos
- **Uso**: se llama en `AppShell.vue` o en vistas protegidas
