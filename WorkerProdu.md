Para producción, necesitamos configurar **Supervisor** en el servidor (aaPanel + Ubuntu). Supervisor es el estándar para mantener workers de Laravel corriendo permanentemente.

---

## Solución para producción

### 1. Conectarse al servidor por SSH

```bash
ssh root@46.62.154.26 -p 49170
```

### 2. Instalar Supervisor (si no está instalado)

```bash
apt update && apt install supervisor -y
```

### 3. Crear archivo de configuración para el worker

```bash
nano /etc/supervisor/conf.d/intermedius-worker.conf
```

Pegá esto:

```ini
[program:intermedius-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /www/wwwroot/api.intermediusg.com/backend/api/artisan queue:work --tries=3 --backoff=30 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=root
numprocs=1
redirect_stderr=true
stdout_logfile=/www/wwwroot/api.intermediusg.com/backend/api/storage/logs/worker.log
stopwaitsecs=3600
```

### 4. Recargar Supervisor

```bash
supervisorctl reread
supervisorctl update
supervisorctl start intermedius-queue:*
```

### 5. Verificar que está corriendo

```bash
supervisorctl status
```

Deberías ver:

```
intermedius-queue:00    RUNNING   pid XXXX, uptime 0:00:XX
```

---

## ¿Qué hace esto?

| Configuración | Efecto |
|---|---|
| `autostart=true` | Inicia automáticamente al bootear el servidor |
| `autorestart=true` | Reinicia el worker si se cae por cualquier razón |
| `--tries=3` | Reintenta jobs fallidos 3 veces |
| `--backoff=30` | Espera 30 segundos entre reintentos |
| `--max-time=3600` | Reinicia el worker cada hora (libera memoria) |
| `numprocs=1` | 1 proceso worker (subir según carga) |

---

## Nota importante sobre el `.env` de producción

En producción, asegurate de que:

```
QUEUE_CONNECTION=database
```

El worker lee los jobs de la tabla `jobs` y los procesa. No uses `sync` en producción.

---

¿Necesitás que te pase el comando exacto para ejecutar en el servidor o podés hacerlo vos?
