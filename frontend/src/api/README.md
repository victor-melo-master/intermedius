# api/ — Cliente HTTP

## `axios.js`
- Instancia axios con `baseURL` desde `VITE_API_URL` (default `http://localhost:8000/api/v1`)
- **Request interceptor**: agrega `Authorization: Bearer {token}` desde `localStorage`
- **Response interceptor**: redirige a login si recibe `401`

### Uso
```javascript
import api from '../api/axios.js'
const { data } = await api.get('/clientes')
const { data } = await api.post('/operaciones', body)
```
