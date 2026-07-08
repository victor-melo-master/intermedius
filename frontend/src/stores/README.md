# stores/ — Pinia Stores

9 stores, una por dominio de la aplicación.

| Store | Archivo | Propósito |
|---|---|---|
| `useAuthStore` | `auth.js` | Login, logout, token, usuario autenticado, roles |
| `useBancosStore` | `bancos.js` | CRUD bancos (list, create) |
| `useClientesStore` | `clientes.js` | CRUD clientes + fetchTrashed + restore |
| `useOperacionesStore` | `operaciones.js` | CRUD operaciones, detalle, verificar, filtros |
| `usePoolStore` | `pool.js` | Pool de pagadores (list, tomar, soltar, pagar, cancelar) |
| `useTasasStore` | `tasas.js` | Tasas vigentes, historial, monedas, publicar |
| `useTasasReferenciaStore` | `tasasReferencia.js` | Tasas de referencia para dashboard |
| `useTitularesStore` | `titulares.js` | CRUD titulares |
| `useUsuariosStore` | `usuarios.js` | CRUD usuarios + toggle activo |

## Convención

Todas usan `defineStore('name', () => { ... })` (Composition API).
Métodos asíncronos con try/catch, el error se maneja en la vista.
