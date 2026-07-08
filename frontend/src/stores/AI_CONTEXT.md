# stores/ — AI Context

Todas usan `defineStore('name', () => { ... })` (Composition API).

## `useAuthStore` (`auth.js`)
- **Estado**: `token` (ref string), `user` (ref object|null)
- **Computed**: `isAuthenticated`, `isAdmin` (user.roles.includes admin/super_admin)
- **Métodos**: `login(email, password)`, `logout()`, `checkRole(role)`
- **Persistencia**: token y user en localStorage (sync manual)

## `useBancosStore` (`bancos.js`)
- **Estado**: `list` (ref array), `loading`, `error`
- **Métodos**: `fetchAll()`, `create(body)`
- **Uso**: Selector de banco en formularios, CRUD en BancosView

## `useClientesStore` (`clientes.js`)
- **Estado**: `list`, `loading`, `error`
- **Métodos**: `fetchAll(search)`, `fetchTrashed(search)`, `create(body)`, `update(id, body)`, `restore(id)`
- **Nota**: fetchTrashed usa `?inactivos=true`, restore usa `POST /clientes/{id}/restaurar`

## `useOperacionesStore` (`operaciones.js`)
- **Estado**: `list`, `current`, `loading`, `error`
- **Métodos**: `fetchAll(params)`, `create(body)`, `update(id, body)`, `fetchDetail(id)`, `verificar(id, body)`
- **Uso**: OperacionesView, OperacionFormView, OperacionDetailView

## `usePoolStore` (`pool.js`)
- **Estado**: `list`, `misOrdenes`, `loading`, `error`
- **Métodos**: `fetchAll()`, `fetchMisOrdenes()`, `tomar(id)`, `soltar(id)`, `pagar(id, body)`, `cancelar(id)`

## `useTasasStore` (`tasas.js`)
- **Estado**: `actuales`, `historial`, `monedas`, `loading`
- **Métodos**: `fetchActuales()`, `fetchHistorial(params)`, `fetchMonedas()`, `publicar(body)`

## `useTasasReferenciaStore` (`tasasReferencia.js`)
- **Estado**: `list`, `loading`
- **Métodos**: `fetchAll()`
- **Uso**: DashboardView (tasas de referencia)

## `useTitularesStore` (`titulares.js`)
- **Estado**: `list`, `loading`, `error`
- **Métodos**: `fetchAll()`, `create(body)`, `update(id, body)`

## `useUsuariosStore` (`usuarios.js`)
- **Estado**: `list`, `loading`, `error`
- **Métodos**: `fetchAll()`, `create(body)`, `update(id, body)`, `toggleActivo(id)`
