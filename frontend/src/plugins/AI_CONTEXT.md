# plugins/ — AI Context

> Plugin de WebSocket para tiempo real.

## echo.js

Configuración de Laravel Echo con Reverb (broadcaster).

```js
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
window.Pusher = Pusher

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
})
export default echo
```

### Variables de entorno requeridas

| Variable | Default | Descripción |
|---|---|---|
| `VITE_REVERB_APP_KEY` | — | Key de la app Reverb |
| `VITE_REVERB_HOST` | `localhost` | Host del servidor Reverb |
| `VITE_REVERB_PORT` | `8080` | Puerto WebSocket |
| `VITE_REVERB_SCHEME` | `http` | `http` o `https` |

### Uso

```js
import echo from '@/plugins/echo'

// Escuchar canal privado
echo.private('channel.name')
    .listen('EventName', (e) => { ... })

// Escuchar canal público
echo.channel('channel.name')
    .listen('EventName', (e) => { ... })
```

### Notas
- El broadcaster es `reverb` (no pusher), aunque usa `pusher-js` como dependencia
- `enabledTransports: ['ws', 'wss']` — solo WebSocket, no HTTP polling
- Actualmente **no se usa activamente** en ninguna vista o composable — está configurado para uso futuro
