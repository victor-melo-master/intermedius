# stores/ — AI Context

> 9 Pinia stores con Composition API (`defineStore('name', () => { ... })`). Cada store encapsula un dominio del negocio.

## Estructura

```
stores/
├── auth.js             → Autenticación y roles
├── operaciones.js      → CRUD operaciones + verificar
├── pool.js             → Pool de pagadores
├── clientes.js         → CRUD clientes + soft-delete
├── tasas.js            → Tasas de cambio
├── tasasReferencia.js  → Tasas de referencia (dashboard)
├── bancos.js           → CRUD bancos
├── titulares.js        → CRUD titulares
└── usuarios.js         → CRUD usuarios
```

---

## useAuthStore (`auth.js`)

**Estado**: `token` (ref string), `user` (ref object|null)

**Computed**:
- `isAuthenticated` — `!!token && !!user`
- `isAdmin` — `user.roles.includes('admin') || user.roles.includes('super_admin')`

**Métodos**:
| Método | API | Descripción |
|---|---|---|
| `login(email, password)` | `POST /auth/login` | Guarda token + user en localStorage |
| `logout()` | `POST /auth/logout` | Limpia token + user |
| `checkRole(role)` | — | Verifica si user tiene el rol indicado |

**Persistencia**: Token y user sincronizados manualmente con `localStorage`.

---

## useOperacionesStore (`operaciones.js`)

**Estado**: `list` (ref array), `current` (ref object|null), `loading`, `error`

**Métodos**:
| Método | API | Descripción |
|---|---|---|
| `fetchAll(params)` | `GET /operaciones` | Listado con filtros (tipo_codigo, estatus, fecha_desde, fecha_hasta, cliente_id, moneda) |
| `fetchOne(id)` | `GET /operaciones/:id` | Detalle con movimientos |
| `create(body)` | `POST /operaciones` | Crear operación |
| `update(id, body)` | `PUT /operaciones/:id` | Editar operación |
| `verificar(id, body)` | `PATCH /operaciones/:id/verificar` | Cambiar estatus a verificado |
| `destroy(id)` | `DELETE /operaciones/:id` | Eliminar |

---

## usePoolStore (`pool.js`)

**Estado**: `list` (ref array), `misOrdenes` (ref array), `loading`, `error`

**Métodos**:
| Método | API | Descripción |
|---|---|---|
| `fetchAll()` | `GET /pool` | Órdenes disponibles |
| `fetchMisOrdenes()` | `GET /pool/mis-ordenes` | Órdenes del usuario actual |
| `tomar(id)` | `POST /pool/:id/tomar` | Tomar una orden |
| `soltar(id)` | `POST /pool/:id/soltar` | Soltar una orden tomada |
| `pagar(id, body)` | `POST /pool/:id/pagar` | Marcar como pagada |
| `cancelar(id)` | `POST /pool/:id/cancelar` | Cancelar orden |

---

## useClientesStore (`clientes.js`)

**Estado**: `list` (ref array), `loading`, `error`

**Métodos**:
| Método | API | Descripción |
|---|---|---|
| `fetchAll(search)` | `GET /clientes?q=...` | Listado con búsqueda |
| `fetchTrashed(search)` | `GET /clientes?inactivos=true` | Clientes eliminados |
| `create(body)` | `POST /clientes` | Crear |
| `update(id, body)` | `PUT /clientes/:id` | Actualizar |
| `restore(id)` | `POST /clientes/:id/restaurar` | Restaurar de papelera |

---

## useTasasStore (`tasas.js`)

**Estado**: `actuales` (ref array), `historial` (ref array), `monedas` (ref array), `loading`

**Métodos**:
| Método | API | Descripción |
|---|---|---|
| `fetchActuales()` | `GET /tasas/actuales` | Tasas vigentes |
| `fetchHistorial(params)` | `GET /tasas/historial` | Historial con filtros |
| `fetchMonedas()` | `GET /monedas` | Catálogo de monedas |
| `publicar(body)` | `POST /tasas` | Publicar nueva tasa |

---

## useTasasReferenciaStore (`tasasReferencia.js`)

**Estado**: `list` (ref array), `loading`

**Métodos**:
| Método | API | Descripción |
|---|---|---|
| `fetchAll()` | `GET /tasas/referencia` | Tasas de referencia para dashboard |

---

## useBancosStore (`bancos.js`)

**Estado**: `list` (ref array), `loading`, `error`

**Métodos**:
| Método | API | Descripción |
|---|---|---|
| `fetchAll()` | `GET /bancos` | Listado |
| `create(body)` | `POST /bancos` | Crear |

---

## useTitularesStore (`titulares.js`)

**Estado**: `list` (ref array), `loading`, `error`

**Métodos**:
| Método | API | Descripción |
|---|---|---|
| `fetchAll()` | `GET /titulares` | Listado |
| `create(body)` | `POST /titulares` | Crear |
| `update(id, body)` | `PUT /titulares/:id` | Actualizar |

---

## useUsuariosStore (`usuarios.js`)

**Estado**: `list` (ref array), `loading`, `error`

**Métodos**:
| Método | API | Descripción |
|---|---|---|
| `fetchAll()` | `GET /usuarios` | Listado |
| `create(body)` | `POST /usuarios` | Crear |
| `update(id, body)` | `PUT /usuarios/:id` | Actualizar |
| `toggleActivo(id)` | `PATCH /usuarios/:id/toggle-activo` | Alternar activo/inactivo |
