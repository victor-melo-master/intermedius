# AI Context — Intermedius (Casa de Cambio)

> Documento generado el 2026-07-14. Sirve de punto de partida para que otra IA continúe el trabajo.

---

## 1. Qué es el proyecto

Intermedius es una plataforma web para una casa de cambio venezolana. Reemplaza un sistema administrativo en Excel por una app web + móvil. Permite gestionar clientes, cuentas bancarias, operaciones de compra/venta de divisas, comisiones, tasas de cambio, pool de pagadores, gastos, reportes y más.

---

## 2. Stack

| Capa | Tecnología |
|---|---|
| Backend | Laravel 11, PHP 8.4 (local) / 8.4 (Docker) |
| Frontend | Vue 3 + Vite + Pinia + Vue Router + Axios |
| BD | MariaDB 10.3 |
| Auth | Laravel Sanctum + Spatie Permissions (roles/permisos) |
| Auditoría | Spatie ActivityLog |
| Reportes | maatwebsite/excel, barryvdh/laravel-dompdf |
| Colas/Jobs | Laravel Horizon + Redis |
| Archivos | MinIO (S3-compatible) vía Flysystem |
| Mail | Mailpit (local) |
| Deploy local | Docker Compose |
| Deploy prod | VPS Hetzner + aaPanel + Nginx (puertos 8081/8082) |

---

## 3. Arquitectura Docker (local)

Todos los servicios corren en Docker Compose. Archivo: `docker-compose.yml`.

| Servicio | Puerto | Descripción |
|---|---|---|
| api | 8000 | Laravel `artisan serve` |
| frontend | 3000 | Vue 3 (Vite dev) |
| db | 3306 | MariaDB 10.3 |
| redis | 6379 | Redis 7 |
| minio | 9000 (API), 9001 (Console) | MinIO S3-compatible |
| mailpit | 8025 (UI), 1025 (SMTP) | Mail pit |
| horizon | — | Laravel Horizon (colas) |
| schedule | — | `artisan schedule:work` |

**Volumes importantes:**
- `api_vendor` — Sobreescribe `/var/www/vendor` dentro de los contenedores api/horizon/schedule. Si se necesita reinstalar dependencias, hay que eliminar este volumen antes de `docker compose up --build`.
- `db_data` — Persistencia de MariaDB.
- `minio_data` — Persistencia de MinIO.

**Build args:** `UID` y `GID` del usuario host (default 1000).

**Nota PHP:** Localmente tenés PHP 8.5, pero los contenedores usan PHP 8.4. Siempre correr `composer update/install` DENTRO de Docker:
```bash
docker compose exec api composer update
```

---

## 4. Estructura del backend

```
api/
  app/
    Http/
      Controllers/
        AuthController.php
        BancoController.php
        CategoriaGastoController.php
        ClienteController.php
        CuentaController.php
        MonedaController.php
        TasasController.php
        TitularController.php
        Api/V1/
          Configuracion/
            ComisionCuentaController.php
            ComisionMetodoPagoController.php
            ComisionOperacionController.php
            ComisionOperadorController.php
            TasaDiariaController.php
          DashboardController.php
          DocumentoController.php
          GastoController.php
          OperacionController.php
          PoolController.php
          ReporteComisionesController.php
          UserController.php
      Middleware/
        Authenticate.php          # Custom: 401 JSON para API
        SanitizeLogs.php
    Models/                       # 16 modelos (ver AI_CONTEXT.md en Models/)
    Jobs/                         # SincronizarTasasJob, SincronizarTasasReferenciaJob
  bootstrap/app.php               # Config de middleware, rutas, excepciones
  config/
    filesystems.php               # Disk s3 con 'throw' => true
    google2fa.php                 # Usa string literal 'svg' (evita error de clase)
  database/
    migrations/                   # Solo 2 migrations (login_attempts, documentos stub)
    schema/mysql.sql              # Schema SQL completo (referencia)
    seed.sql                      # Seed data
  routes/api.php                  # Todas las rutas API bajo prefix v1
  docker/
    entrypoint.sh                 # RUN_MIGRATIONS guard + MinIO wait + bucket creation
    mysql/00-init.sh              # Solo crea la DB
```

---

## 5. Modelos Eloquent (16)

Ver `api/app/Models/AI_CONTEXT.md` para documentación completa de cada modelo con relaciones, scopes, casts y events.

Resumen rápido:
- **Titular** → Cuenta, User, CategoriaGasto, ComisionOperador
- **Cliente** → Cuenta, Documento, Operacion
- **Cuenta** → Titular XOR Cliente + Banco + Moneda
- **Operacion** → Movimiento, ComisionOperacion, TipoOperacion, Cliente, User (operador/verificador/pagador)
- **Movimiento** → Operacion, Cuenta, Moneda
- **Moneda** → Cuenta, Movimiento, Comisiones*, TasaDiaria, TasaMercado
- **Banco** → Cuenta, ComisionCuenta
- **TipoOperacion** → Operacion, ComisionOperador
- **ComisionOperacion** → MorphTo origen + Operacion + Movimiento
- **TasaDiaria** → Moneda(base/cotizada), User, Operacion
- **TasaMercado** → Moneda(base/cotizada) — datos crudos de fuentes externas
- **Documento** → Cliente (archivos subidos a MinIO)
- **User** → Spatie HasRoles + Sanctum HasApiTokens + MustVerifyEmail + SoftDeletes
- **LoginAttempt** — Intentos de login fallidos
- **CategoriaGasto** → Titular, Operacion

---

## 6. Rutas API

Todas bajo `/api/v1/`. Auth por Sanctum token.

**Públicas:**
- `POST /api/v1/auth/login` (throttle 5/min)
- `POST /api/v1/auth/verificar-email` (throttle 10/min)
- `GET /api/v1/email/verify/{id}/{hash}` (signed)
- `GET /api/v1/documentos/{id}/preview` (token en query param)
- `GET /api/v1/documentos/{id}/download` (token en query param)

**Protegidas (auth:sanctum):**
- `POST /api/v1/auth/logout`, `GET /api/v1/auth/me`
- CRUD: titulares, bancos, monedas, cuentas, clientes, categorias-gasto, operaciones, usuarios
- `GET/POST /api/v1/clientes/{id}/documentos`, `DELETE /api/v1/documentos/{id}`
- `GET /api/v1/clientes/{id}/cuentas`, `GET /api/v1/clientes/{id}/operaciones`
- Pool de pagadores: tomar, soltar, pagar, cancelar
- Configuración: tasas-diarias, comisiones-cuenta, comisiones-operador, comisiones-metodo-pago
- Reportes: comisiones-operadores
- Dashboard: general, tasas-referencia, resumen
- Bitácora: `GET /api/v1/admin/bitacora` (solo super_admin)

**Roles:** admin, super_admin, pagador, contador

---

## 7. Archivos y Documentos (MinIO)

- **Disk:** `s3` (MinIO local)
- **Controller:** `Api/V1/DocumentoController.php`
- **Upload:** `$request->file('archivo')->store('documentos/{cliente_id}', 's3')` usando `$archivo->get()` para obtener el contenido raw
- **Preview:** `Storage::disk('s3')->exists($path)` → `Storage::disk('s3')->get($path)` con Content-Type correcto
- **Download:** Misma lógica pero con `Content-Disposition: attachment`
- **Modelo:** `Documento` → belongsTo `Cliente`
- **Relación:** `Cliente` → `documentos()` HasMany
- **archivos subidos a:** `s3://documentos/{cliente_id}/`

---

## 8. Entrypoint y Migraciones

`docker/entrypoint.sh`:
1. Espera MariaDB (loop polling)
2. Si `RUN_MIGRATIONS=true`, ejecuta `php artisan migrate --force` (solo el contenedor `api` tiene esta env var)
3. Espera MinIO (loop polling)
4. Verifica/crea el bucket S3 si no existe

**IMPORTANTE:** Las migraciones de las tablas core (users, titulares, bancos, monedas, cuentas, clientes, etc.) NO existen como migraciones de Laravel. Solo existen como SQL en `api/database/schema/mysql.sql`. Las tablas se crean por el `00-init.sh` o por importación previa. Solo hay 2 migraciones en Laravel: `login_attempts` y `documentos` (esta última es un stub incompleto).

---

## 9. Bugs corregidos recientemente

### Documentos (commit 01eafd7)
- **Problema:** `Cliente` no tenía relación `documentos()` → 500 al listar documentos
- **Fix:** Agregada `documentos()` HasMany en Cliente.php
- **Dependencias:** Agregados `aws/aws-sdk-php` y `league/flysystem-aws-s3-v3` a composer.json
- **entrypoint:** Guard `RUN_MIGRATIONS` para que solo `api` migre

### 401 para no autenticados (commit ab438bc)
- **Problema:** Requests sin token a rutas protegidas daban 500 (`Route [login] not defined`) en vez de 401
- **Fix:** `redirectGuestsTo()` en `bootstrap/app.php` retorna 401 JSON para requests API
- **Custom middleware:** `app/Http/Middleware/Authenticate.php` creado (aunque no se usa activamente, el fix está en bootstrap)

### Google2FA (commit ab438bc)
- **Problema:** `PragmaRX\Google2FALaravel\Support\Constants` not found en artisan (horizon/schedule)
- **Fix:** `config/google2fa.php` usa string literal `'svg'` en vez de la clase Constants

---

## 10. Pendientes / Deuda técnica

### Críticos
1. **Migraciones faltantes:** Las tablas core (users, titulares, bancos, monedas, cuentas, clientes, tipos_operacion, operaciones, movimientos, comisiones_*, tasas_*) NO tienen migraciones de Laravel. El schema existe en `database/schema/mysql.sql`. Hay que crear las migraciones o importar el schema directamente.
2. **Migración documentos incompleta:** `2026_07_13_123742_create_documentos_table.php` es un stub que solo crea `id` + `timestamps`. La tabla real tiene más columnas (posiblemente creadas por importación manual o un fix anterior).

### Menores
3. **Deprecación Spatie:** `activity(): Implicitly marking parameter $logName as nullable is deprecated` — warning molesto en logs de horizon/schedule.
4. **CI/CD deshabilitado:** Los workflows de GitHub Actions (`.github/workflows/`) están comentados.
5. **Production:** El deploy apunta a `api.intermediusg.com` (puerto 8081) y `admin.intermediusg.com` (puerto 8082) con aaPanel.
6. **.env production tiene credenciales reales** (Gmail password, etc.) — revisar seguridad.

---

## 11. Cómo levantar el entorno

```bash
# Limpiar todo y reconstruir
docker compose down -v          # -v elimina volumes (incluido api_vendor stale)
docker compose up --build -d

# Verificar logs
docker compose logs api --tail=20
docker compose logs horizon --tail=10
docker compose logs schedule --tail=10

# Correr composer dentro de Docker
docker compose exec api composer update

# Abrir tinker
docker compose exec api php artisan tinker

# MinIO Console
# http://localhost:9001 (minioadmin / minioadmin)
```

---

## 12. Archivos clave para modificar

| Archivo | Para qué |
|---|---|
| `api/bootstrap/app.php` | Middleware, routing, excepciones |
| `api/routes/api.php` | Todas las rutas API |
| `api/app/Models/*.php` | Modelos Eloquent |
| `api/app/Http/Controllers/**/*.php` | Controladores |
| `api/config/filesystems.php` | Config S3/MinIO |
| `api/config/google2fa.php` | Config 2FA |
| `docker-compose.yml` | Servicios Docker |
| `docker/entrypoint.sh` | Init del contenedor API |
| `docker/mysql/00-init.sh` | Solo crea la DB |
| `docker/mysql/seed.sql` | Seed data (roles, users, monedas, etc.) |
| `api/database/schema/mysql.sql` | Schema SQL completo (referencia) |
| `api/composer.json` | Dependencias PHP |

---

## 13. Commits recientes (main)

```
84f20ca merge: dockerizar-proyecto into main
ab438bc fix: 401 JSON para requests no autenticados + google2fa config fix
01eafd7 fix: documentos funcional — relación Cliente, dependencias S3, solo api migra
873846b fix: separar init DB de migraciones y sincronizar composer.lock
9b6a2ec fix: migracion login_attempts creaba tabla documentos por error
487b0ab fix: previsualizacion y descarga de documentos en MinIO
```

---

## 14. Git

- **Branch activa:** `main` (branch `dockerizar-proyecto` también existe, ya merged)
- **Remote:** `origin` (GitHub)
- **No hay CI activo** — los workflows están comentados
