# api/ — AI Context

> Cliente HTTP Axios con interceptors para autenticación y manejo de errores.

## axios.js

Archivo único. Crea y exporta una instancia axios configurada.

### Configuración

```js
baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'
headers: { Accept: 'application/json' }
```

### Request interceptor

- Lee `token` de `localStorage`
- Si existe, agrega `Authorization: Bearer {token}`

### Response interceptor

- Si response es `401` (Unauthenticated):
  - Limpia `localStorage` (token + user)
  - Redirige a `/login` via `window.location.href`

### Variables de entorno

| Variable | Default | Descripción |
|---|---|---|
| `VITE_API_URL` | `http://localhost:8000/api/v1` | Base URL del backend |

### Uso

```js
import api from '../api/axios.js'

// GET
const { data } = await api.get('/clientes', { params: { q: 'busqueda' } })

// POST
const { data } = await api.post('/operaciones', body)

// PUT
const { data } = await api.put(`/clientes/${id}`, body)

// PATCH
const { data } = await api.patch(`/clientes/${id}/restore`)

// DELETE
await api.delete(`/clientes/${id}`)
```

### Endpoints del backend

| Método | Endpoint | Descripción |
|---|---|---|
| POST | `/auth/login` | Login |
| POST | `/auth/logout` | Logout |
| GET | `/auth/me` | Usuario actual |
| POST | `/auth/verificar-email` | Verificar email |
| GET | `/operaciones` | Listar operaciones |
| POST | `/operaciones` | Crear operación |
| GET | `/operaciones/:id` | Detalle operación |
| PUT | `/operaciones/:id` | Editar operación |
| PATCH | `/operaciones/:id/verificar` | Verificar operación |
| DELETE | `/operaciones/:id` | Eliminar operación |
| GET | `/pool` | Pool de órdenes |
| GET | `/pool/mis-ordenes` | Órdenes propias |
| POST | `/pool/:id/tomar` | Tomar orden |
| POST | `/pool/:id/soltar` | Soltar orden |
| POST | `/pool/:id/pagar` | Pagar orden |
| POST | `/pool/:id/cancelar` | Cancelar orden |
| GET | `/clientes` | Listar clientes |
| POST | `/clientes` | Crear cliente |
| PUT | `/clientes/:id` | Editar cliente |
| DELETE | `/clientes/:id` | Eliminar cliente |
| PATCH | `/clientes/:id/restore` | Restaurar cliente |
| GET | `/cuentas` | Listar cuentas |
| GET | `/bancos` | Listar bancos |
| POST | `/bancos` | Crear banco |
| GET | `/titulares` | Listar titulares |
| POST | `/titulares` | Crear titular |
| PUT | `/titulares/:id` | Editar titular |
| GET | `/usuarios` | Listar usuarios |
| POST | `/usuarios` | Crear usuario |
| PUT | `/usuarios/:id` | Editar usuario |
| PATCH | `/usuarios/:id/toggle-activo` | Toggle activo |
| GET | `/monedas` | Catálogo de monedas |
| GET | `/tasas/actuales` | Tasas vigentes |
| GET | `/tasas/historial` | Historial tasas |
| POST | `/tasas` | Publicar tasa |
| GET | `/tasas/referencia` | Tasas referencia dashboard |
| GET | `/configuracion/tasas-vigentes` | Tasas vigentes (config) |
| GET | `/configuracion/tasas-diarias/historico` | Histórico tasas diarias |
| GET | `/configuracion/comisiones-metodo-pago` | Listar comisiones |
| POST | `/configuracion/comisiones-metodo-pago` | Crear comisión |
| PUT | `/configuracion/comisiones-metodo-pago/:id` | Editar comisión |
| DELETE | `/configuracion/comisiones-metodo-pago/:id` | Eliminar comisión |
| GET | `/reportes/comisiones-operadores` | Reporte comisiones |
| POST | `/reportes/comisiones-operadores/exportar` | Exportar reporte |
