# api/ — AI Context

## `axios.js`

Archivo único. Crea y exporta una instancia axios configurada.

### Configuración
- `baseURL`: `import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'`
- `headers`: `{ Accept: 'application/json' }`

### Request interceptor
- Lee `token` de `localStorage`
- Si existe, agrega `Authorization: Bearer {token}`

### Response interceptor
- Si response es `401` (Unauthenticated):
  - Limpia `localStorage` (token + user)
  - Redirige a `/login` via `window.location.href`

### Uso
```javascript
import api from '../api/axios.js'

// GET
const { data } = await api.get('/clientes', { params: { q: 'busqueda' } })

// POST
const { data } = await api.post('/operaciones', body)

// PUT
const { data } = await api.put(`/clientes/${id}`, body)

// DELETE
await api.delete(`/clientes/${id}`)
```
