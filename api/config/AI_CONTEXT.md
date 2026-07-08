# Config — AI Context

## `sistema.php` — Configuración del sistema

```php
<?php

return [
    'pares_principales' => ['USD/VES', 'USDT/VES'],
    // Pares de monedas que el sistema debe monitorear para alertas de tasa faltante.
    // Formato: 'BASE/COTIZADA'.
    // Usado por: AlertarTasasFaltantesJob, endpoint /api/v1/dashboard/general.

    'moneda_referencia' => 'USD',
    // Moneda usada como denominador común para equivalencias contables y
    // cálculos de ganancias. Todas las conversiones pasan por esta moneda.

    'moneda_local' => 'VES',
    // Moneda fiat local. Excluida del costeo FIFO (no se crea lote para ella).
    // Usado por: LoteService (skips local currency costing).
];
```

## `fifo.php` — Configuración del motor FIFO

```php
<?php

return [
    'permitir_sobregiro' => env('FIFO_PERMITIR_SOBREGIRO', true),
    // Si es false, una operación que intente egresar más cantidad de la que
    // existe en lotes lanzará ValidationException en lugar de crear un consumo
    // con lote_id null.
    // Usado por: FifoService::consumir()

    'politica_costo_sobregiro' => env('FIFO_POLITICA_COSTO_SOBREGIRO', 'tasa_movimiento'),
    // Define cómo se calcula el costo_unitario_usd del consumo en sobregiro.
    // Valores posibles:
    //   'tasa_movimiento' : usa la tasa_a_usd del movimiento (ganancia = 0).
    //   'ultimo_lote'     : usa el costo del último lote histórico del titular
    //                       en esa moneda; fallback a tasa_movimiento.
    //   'cero'            : asume costo cero; TODO el monto es ganancia.
    //                       SOLO para auditorías. NO recomendado en producción.
    // Usado por: FifoService::consumir()

    'tolerancia_consumo' => 0.0001,
    // Cantidad mínima en unidades para considerar un lote "no agotado".
    // Evita que residuos de redondeo (0.00000001 USDT) generen consumos espurios.
    // Usado por: FifoService (comparaciones de saldo).
];
```

## `permission.php` — Configuración de Spatie Permission

```php
<?php

return [
    'models' => [
        'permission' => Permission::class,
        // Modelo Eloquent usado para permisos.

        'role' => Role::class,
        // Modelo Eloquent usado para roles.
    ],

    'table_names' => [
        'roles'                         => 'roles',
        'permissions'                   => 'permissions',
        'model_has_permissions'         => 'model_has_permissions',
        'model_has_roles'               => 'model_has_roles',
        'role_has_permissions'          => 'role_has_permissions',
    ],

    'column_names' => [
        'role_pivot_key'       => null,      // default: 'role_id'
        'permission_pivot_key' => null,      // default: 'permission_id'
        'model_morph_key'      => 'model_id',
        'team_foreign_key'     => 'team_id',
    ],

    'register_permission_check_method' => true,
    // Registra el método de verificación en el Gate de Laravel.

    'register_octane_reset_listener' => false,
    // Refresca permisos en eventos Octane (TickTerminated, etc.).

    'events_enabled' => false,
    // Dispara eventos RoleAttached, RoleDetached, PermissionAttached, etc.

    'teams' => false,
    // Habilita la feature de equipos multi-tenant.

    'team_resolver' => DefaultTeamResolver::class,
    // Clase que resuelve el team_id actual.

    'use_passport_client_credentials' => false,
    // Usa Passport Client Credentials Grant para verificar permisos.

    'display_permission_in_exception' => false,
    // Muestra nombres de permisos requeridos en mensajes de excepción.

    'display_role_in_exception' => false,
    // Muestra nombres de roles requeridos en mensajes de excepción.

    'enable_wildcard_permission' => false,
    // Habilita permisos con wildcard (ej: "posts.*").

    'cache' => [
        'expiration_time' => DateInterval::createFromDateString('24 hours'),
        // TTL del cache de permisos. Se limpia automáticamente al modificar roles/permisos.

        'key' => 'spatie.permission.cache',
        // Clave de cache para almacenar todos los permisos.

        'store' => 'default',
        // Driver de cache (cualquier store definido en cache.php).
    ],
];
```

## `reportes.php` — Configuración de reportes

```php
<?php

return [
    'comisiones_operadores' => [
        'enviar_email'  => env('REPORTE_COMISIONES_EMAIL', false),
        // Habilita el envío automático del reporte mensual de comisiones por email.

        'destinatarios' => env('REPORTE_COMISIONES_DESTINATARIOS', ''),
        // CSV de emails destino (ej: "admin@empresa.com,contador@empresa.com").
        // Solo se usa si enviar_email = true y la lista no está vacía.

        'storage_path'  => 'reportes/comisiones',
        // Directorio base dentro de storage/app/ donde se guardan los reportes.
        // Usado por: GenerarReporteComisionesJob.
    ],
];
```

## `horizon.php` — Configuración de Laravel Horizon

```php
<?php

return [
    'name' => env('HORIZON_NAME'),
    // Nombre de la instancia Horizon (visible en UI y notificaciones).
    // Útil al correr múltiples instancias en la misma app.

    'domain' => env('HORIZON_DOMAIN'),
    // Subdominio donde vive Horizon. null = mismo dominio que la app.

    'path' => env('HORIZON_PATH', 'horizon'),
    // URI path de acceso al dashboard de Horizon.

    'use' => 'default',
    // Conexión Redis donde Horizon almacena metadatos (supervisores,
    // jobs fallidos, métricas, etc.).

    'prefix' => env('HORIZON_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'),
    // Prefijo para todas las keys de Redis de Horizon. Previene colisiones
    // entre múltiples instalaciones en el mismo servidor Redis.

    'middleware' => ['web'],
    // Middleware aplicado a todas las rutas de Horizon.

    'waits' => [
        'redis:default' => 60,
    ],
    // Umbral en segundos para disparar LongWaitDetected, por conexión/cola.

    'trim' => [
        'recent'        => 60,    // min — jobs recientes
        'pending'       => 60,    // min — jobs pendientes
        'completed'     => 60,    // min — jobs completados
        'recent_failed' => 10080, // min (~7 days) — fallidos recientes
        'failed'        => 10080, // min (~7 days) — fallidos
        'monitored'     => 10080, // min (~7 days) — monitoreados
    ],
    // Tiempo de retención (en minutos) para cada categoría de jobs en el dashboard.

    'silenced' => [],
    // Jobs a silenciar (no aparecen en el listado de completados en el dashboard).

    'silenced_tags' => [],
    // Tags a silenciar (no aparecen en el listado de completados en el dashboard).

    'metrics' => [
        'trim_snapshots' => [
            'job'   => 24, // snapshots de jobs a conservar
            'queue' => 24, // snapshots de colas a conservar
        ],
    ],
    // Cantidad de snapshots de métricas a conservar para las gráficas.

    'fast_termination' => false,
    // Si es true, "horizon:terminate" no espera a que todos los workers
       // terminen (a menos que se pase --wait). Acelera deploys.

    'memory_limit' => 64,
    // MB — límite de memoria del supervisor maestro. Al excederse se reinicia.

    'defaults' => [
        'supervisor-1' => [
            'connection'       => 'redis',
            'queue'            => ['default'],
            'balance'          => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses'     => 1,
            'maxTime'          => 0,
            'maxJobs'          => 0,
            'memory'           => 128,
            'tries'            => 1,
            'timeout'          => 60,
            'nice'             => 0,
        ],
    ],
    // Configuración base del supervisor-1, usada en todos los entornos
    // como plantilla. Valores por ambiente en 'environments' sobreescriben.

    'environments' => [
        'production' => [
            'supervisor-1' => [
                'maxProcesses'     => 10,
                'balanceMaxShift'  => 1,
                'balanceCooldown'  => 3,
            ],
        ],
        'local' => [
            'supervisor-1' => [
                'maxProcesses' => 3,
            ],
        ],
    ],
    // Configuraciones específicas por entorno. Se mergean sobre 'defaults'.
    // production: 10 procesos, autoescalado conservador.
    // local: 3 procesos.

    'watch' => [
        'app',
        'bootstrap',
        'config/**/*.php',
        'database/**/*.php',
        'public/**/*.php',
        'resources/**/*.php',
        'routes',
        'composer.lock',
        'composer.json',
        '.env',
    ],
    // Directorios y archivos vigilados por `horizon:listen`. Al detectar
    // cambios, Horizon se reinicia automáticamente.
];
```

---

## Configs estándar de Laravel (resumen)

| Archivo | Propósito |
|---|---|
| `app.php` | Nombre, entorno, debug, URL, timezone, locale, proveedores, aliases |
| `auth.php` | Guards (sanctum, web), providers (users), passwords |
| `cache.php` | Drivers (redis, file), stores, prefix, ttl por defecto |
| `cors.php` | CORS paths, allowed origins/methods/headers |
| `database.php` | Conexiones (mysql, redis), defaults, collation |
| `filesystems.php` | Disks (local, s3, public), cloud default |
| `logging.php` | Channels (stack, single, daily, syslog), levels |
| `mail.php` | Mailer (smtp), host, port, credentials, from address |
| `queue.php` | Default driver (redis), connections, failed jobs table |
| `sanctum.php` | Stateful domains, expiration, middleware aliases |
| `services.php` | Credenciales de terceros (mailgun, postmark, ses) |
| `session.php` | Driver (redis), lifetime, cookie, domain, secure, same_site |

## Configs de paquetes (resumen)

| Archivo | Propósito |
|---|---|
| `activitylog.php` | Spatie Activitylog — modelo, tabla, registro por defecto |
| `dompdf.php` | DomPDF — orientación, papel, opciones de render |
| `excel.php` | Laravel Excel — exports, imports, settings, cache |
