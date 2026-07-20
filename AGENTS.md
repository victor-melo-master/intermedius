# AGENTS — Intermedius

## Rendimiento (diagnóstico Jul 2026)

### 🔴 Críticos (arreglar ya)
1. **Xdebug activo en prod** — `docker/php/php.ini:24` → `xdebug.mode = off`
2. **Opcache deshabilitado** — `docker/php/php.ini:13` → `opcache.enable = 1`
3. **Cache/Queue en DB, no Redis** — `.env:37,39` → `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`

### 🟡 Moderados
4. **N+1 queries en RegistroOperacionService** — `Cuenta::find()` por cada movimiento en líneas 64,107,139,179. Batch con `whereIn()`.
5. **Dashboard resumen sin paginación** — `DashboardController.php:143` usa `->get()`. Cambiar a `chunk()` o paginación.
6. **Pool eager load 11 relaciones** — Revisar si todas son necesarias en el listado.

### 🟢 Menores
7. **LIKE %search% en Clientes** — Usar FULLTEXT index existente.
8. **Sesiones en MySQL** — Migrar a Redis o file.
