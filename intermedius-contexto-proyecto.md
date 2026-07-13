# Intermedius — Sistema Administrativo para Casa de Cambio
## Documento de contexto para programador

---

## 1. Descripción del proyecto

**Cliente:** Intermedius (casa de cambio venezolana)
**Objetivo:** Reemplazar un sistema administrativo basado en Excel por una aplicación web/móvil.
**Estado actual:** Backend Laravel parcialmente implementado, en servidor de producción, sin frontend Flutter.

---

## 2. Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend API | Laravel 11.51 (PHP 8.3) |
| Frontend | Flutter (web + móvil desde un solo código) |
| Base de datos | MariaDB 10.3.31 |
| Autenticación | Laravel Sanctum + Spatie Permissions |
| Auditoría | Spatie ActivityLog |
| Reportes Excel | maatwebsite/excel |
| Reportes PDF | barryvdh/laravel-dompdf |
| Deploy | Hetzner VPS con Plesk |
| CI/CD | GitHub Actions → deploy automático vía SSH |

---

## 3. Repositorio y servidor

- **Repositorio:** https://github.com/victor-melo-master/intermedius.git
- **Estructura del repo:** monorepo con `/api` (Laravel) y `/app` (Flutter)
- **Servidor:** intermedius.crececrm.com (IP: 5.9.97.10)
- **Ruta en servidor:** `/var/www/vhosts/crececrm.com/intermedius.crececrm.com/api`
- **Document Root Plesk:** apunta a `api/public`
- **Base de datos:** `intermedius_casa_cambio` en MariaDB local

**Credenciales de BD:** Ver `api/.env` en el servidor de producción (nunca commitear credenciales reales).

**Usuario admin del sistema:** Crear vía `php artisan db:seed --class=AdminUserSeeder` en producción. Usar contraseña fuerte (min 12 chars, mixed case, números, símbolos).

---

## 4. Contexto del negocio

La empresa maneja:
- Compra/venta de USD contra VES (bolívares)
- Cambios multimoneda: USD, USDT, EUR, VES, COP
- ~40 cuentas bancarias venezolanas (Banesco, Venezuela, Mercantil, Bancamiga, Tesoro, Bancaribe, Provincial)
- ~29 plataformas/wallets no bancarias (Binance, Trust Wallet, Zelle, Zinli, Mercantil Panamá, BofA, etc.)
- ~600 clientes activos
- Operadores/titulares: Ale, Karol, Sarah, Eve, Joh, Bel, Eduard, Beatriz, Ana Karina, Alexander, etc.
- <10 usuarios concurrentes del sistema
- Migración pendiente del historial completo del Excel

**Operaciones principales (de las hojas del Excel):**
1. `BOLIVARES` — compra/venta de USD vs VES
2. `DOLARES` — movimientos USD/USDT/EUR entre plataformas
3. `CAMBIOS` — cambios multimoneda con ~17 pares (USDT/CASH, ZELLE/USDT, EUR/USD, etc.)
4. `GASTOS` — gastos operativos categorizados
5. `COMISIONES` — comisiones por operador

---

## 5. Arquitectura del backend

### Principio central: Ledger de partida doble

Toda operación genera N movimientos. La regla invariante:
> La suma de movimientos de una operación convertidos a USD debe ser cero (o la ganancia).

Esto reemplaza los "verificadores" manuales del Excel.

### Estructura de directorios relevante

```
api/
├── app/
│   ├── Http/Controllers/Api/V1/
│   │   ├── AuthController.php
│   │   ├── Configuracion/
│   │   │   ├── TasaDiariaController.php
│   │   │   ├── ComisionCuentaController.php
│   │   │   ├── ComisionOperadorController.php
│   │   │   ├── ComisionMetodoPagoController.php
│   │   │   └── ComisionOperacionController.php
│   │   ├── OperacionController.php
│   │   ├── TitularController.php
│   │   ├── BancoController.php
│   │   ├── MonedaController.php
│   │   ├── CuentaController.php
│   │   └── ClienteController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Titular.php
│   │   ├── Banco.php
│   │   ├── Moneda.php
│   │   ├── Cuenta.php
│   │   ├── Cliente.php
│   │   ├── CategoriaGasto.php
│   │   ├── TipoOperacion.php
│   │   ├── Operacion.php
│   │   ├── Movimiento.php
│   │   ├── TasaMercado.php
│   │   ├── TasaDiaria.php
│   │   ├── ComisionCuenta.php
│   │   ├── ComisionOperador.php
│   │   ├── ComisionMetodoPago.php
│   │   └── ComisionOperacion.php
│   ├── Services/
│   │   ├── Configuracion/
│   │   │   ├── TasaDiariaService.php
│   │   │   └── CalculadorComisionesService.php
│   │   └── Operaciones/
│   │       └── RegistroOperacionService.php
│   ├── Jobs/
│   │   ├── AlertarTasasFaltantesJob.php
│   │   └── GenerarReporteMensualComisionesJob.php
│   └── Providers/
│       └── AppServiceProvider.php
├── database/
│   ├── migrations/ (27 migraciones ejecutadas)
│   └── seeders/
│       ├── CatalogosBaseSeeder.php
│       └── AdminUserSeeder.php
└── routes/
    ├── api.php
    └── console.php
```

---

## 6. Base de datos — Tablas implementadas

### Maestros/Catálogos
- `titulares` — personas propietarias de cuentas (Ale, Karol, Sarah, etc.)
- `bancos` — bancos venezolanos y extranjeros
- `monedas` — VES, USD, USDT, EUR, COP
- `cuentas` — cuentas bancarias y wallets, agrupadas por titular y banco
- `clientes` — ~600 clientes con saldo
- `categorias_gasto` — categorías de gastos operativos
- `tipos_operacion` — venta_usd, compra_usd, cambio_multimoneda, gasto, etc.

### Ledger
- `operaciones` — encabezado de cada transacción
- `movimientos` — detalle de cada transacción (partida doble)

### Tasas y comisiones
- `tasas_mercado` — historial de tasas BCV/Binance capturadas automáticamente
- `tasas_diarias` — tasas operativas definidas por el admin (con vigencia timestamp)
- `comisiones_cuenta` — comisiones por cuenta bancaria
- `comisiones_operador` — comisiones por operador/titular
- `comisiones_metodo_pago` — comisiones por método de pago
- `comisiones_operacion` — snapshot de comisiones aplicadas a cada operación

### Sistema
- `users` — usuarios con titular_id, activo, last_login_at, softDeletes
- `personal_access_tokens` — tokens Sanctum
- `roles`, `permissions`, `model_has_roles`, etc. — Spatie Permissions
- `activity_log` — auditoría completa Spatie

### Columnas clave de `operaciones`
- `ganancia_bruta_usd` / `ganancia_bruta_ves` — ganancia antes de comisiones
- `ganancia_neta_usd` / `ganancia_neta_ves` — ganancia después de comisiones
- `total_comisiones_usd` / `total_comisiones_ves`
- `tasa_aplicada` — tasa efectiva usada en la operación
- `tasa_sugerida` — tasa del día que sugirió el sistema
- `tasa_diaria_id` — FK a tasas_diarias
- `sin_tasa_referencia` — flag cuando se operó sin tasa del día configurada

---

## 7. Roles del sistema

| Rol | Permisos |
|---|---|
| `super_admin` | Acceso total, gestión de usuarios y configuración |
| `admin` | Operaciones completas, sin gestión de usuarios |
| `operador` | Registra operaciones, ve sus cuentas asociadas |
| `contador` | Solo lectura, puede exportar y verificar |
| `lectura` | Solo lectura total |

---

## 8. Endpoints API implementados

### Autenticación (públicos)
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/me`

### Catálogos (requieren auth:sanctum)
- `GET/POST /api/v1/titulares`
- `GET/POST /api/v1/bancos`
- `GET/POST /api/v1/monedas`
- `GET/POST /api/v1/cuentas`
- `GET/POST /api/v1/clientes`
- `GET/POST /api/v1/categorias-gasto`

### Configuración de tasas (lectura: todos / escritura: admin)
- `GET /api/v1/configuracion/tasas-vigentes`
- `GET /api/v1/configuracion/tasas-diarias`
- `POST /api/v1/configuracion/tasas-diarias` *(solo admin/super_admin)*
- `GET /api/v1/configuracion/tasas-diarias/historial/{base}/{cotizada}`

### Configuración de comisiones (solo admin)
- `GET/POST /api/v1/configuracion/comisiones-cuenta`
- `GET/POST /api/v1/configuracion/comisiones-operador`
- `GET/POST /api/v1/configuracion/comisiones-metodo-pago`

### Comisiones de operaciones
- `GET /api/v1/operaciones/{operacion}/comisiones` *(admin/super_admin/contador)*
- `PATCH /api/v1/operaciones/{operacion}/comisiones/{comision}` *(solo admin/super_admin)*

### Operaciones
- `GET/POST /api/v1/operaciones`
- `GET /api/v1/operaciones/{operacion}`
- `PATCH /api/v1/operaciones/{operacion}/verificar`
- `DELETE /api/v1/operaciones/{operacion}`

### Reportes (admin/contador)
- `GET /api/v1/reportes/comisiones-operadores`
- `POST /api/v1/reportes/comisiones-operadores/exportar` (body: {desde, hasta, formato: 'excel'|'pdf'})

---

## 9. Servicios principales

### TasaDiariaService
- `publicar(array $payload, User $admin)` — publica nueva tasa cerrando la anterior
- `obtenerVigente(int $monedaBaseId, int $monedaCotizadaId, ?Carbon $momento)` — tasa vigente en un momento
- `validarTasaEfectiva(TasaDiaria $sugerida, float $tasaEfectiva, string $direccion)` — valida que la tasa del operador sea favorable a la casa
- `identificarPar(Operacion $op)` — identifica el par de monedas principal de una operación

### CalculadorComisionesService
- `calcularParaOperacion(Operacion $op)` — calcula todas las comisiones aplicables
- `aplicarAOperacion(Operacion $op)` — aplica y guarda comisiones (idempotente)
- `recalcularTotalesOperacion(Operacion $op)` — recalcula ganancia neta
- `editarComision(ComisionOperacion $com, array $nuevoValor, User $admin, string $razon)` — edita con auditoría

### RegistroOperacionService
- `registrar(array $payload, User $operador)` — registra operación completa con tasa + comisiones

---

## 10. Reglas de negocio críticas

1. **Tasas del día:** El admin publica tasas diarias. El operador puede usar una tasa distinta SOLO si es favorable a la casa (venta: tasa >= sugerida; compra: tasa <= sugerida). Si no hay tasa del día, se usa la última publicada y se marca `sin_tasa_referencia = true`.

2. **Comisiones:** Se calculan automáticamente al registrar una operación. Solo admin puede editarlas post-facto y queda en bitácora.

3. **Ganancia bruta vs neta:** `ganancia_bruta` = diferencia de tasas × monto. `ganancia_neta` = ganancia_bruta − total_comisiones.

4. **FIFO:** Se calcula por (titular_id, moneda_id). No implementado aún (Fase 4-C pendiente).

5. **Tasas históricas son inmutables:** Cada movimiento guarda la tasa del momento. Nunca se recalcula con tasa actual.

6. **BCV/Binance son SOLO referenciales:** No se usan para calcular ganancia. Solo se muestran en dashboard para que el admin compare al definir sus tasas operativas.

---

## 11. Jobs programados

| Job | Horario | Función |
|---|---|---|
| `AlertarTasasFaltantesJob` | 8:00 AM y 2:00 PM | Alerta por email si falta publicar tasa del día |
| `GenerarReporteMensualComisionesJob` | Día 1 de cada mes 6:00 AM | Genera reporte Excel+PDF de comisiones del mes anterior |

---

## 12. Plan de fases

### ✅ Completadas
- **Fase 1:** Maestros y autenticación (titulares, bancos, monedas, cuentas, clientes, roles)
- **Fase 2:** Ledger de operaciones y movimientos
- **Fase 2.5:** Módulo de configuración (tasas diarias, comisiones por capas, bitácora, reportes)

### 🔄 Pendientes
- **Fase 3:** Integración tasas BCV (dolarapi.com) y Binance P2P (USDT/VES) — job sincronización
- **Fase 4-A:** Módulo de gastos
- **Fase 4-C:** FIFO — costeo por lotes (titular_id, moneda_id)
- **Fase 5:** Dashboards (general, operativo, contable) con tasas en vivo
- **Fase 6:** Pulido, auditoría, hardening, capacitación
- **Fase 7:** Importador desde Excel histórico del cliente
- **Fase 8:** Switchover (cliente deja Excel, usa el sistema)

### Flutter (Fase pendiente completa)
El frontend Flutter NO está implementado. Solo existe el scaffolding en `/app`. Toda la lógica está en el API.

---

## 13. Integraciones externas pendientes

### dolarapi.com (BCV)
- Endpoint público, sin auth
- Trae USD/VES BCV, paralelo, EUR
- Cron cada 15 min
- Guardar en `tasas_mercado` con fuente='bcv'

### Binance P2P (USDT/VES)
- Endpoint no oficial: `bapi/c2c/v2/friendly/c2c/adv/search`
- Sin auth, pero sujeto a cambios
- Capturar compra Y venta (ambas tasas + spread)
- Guardar en `tasas_mercado` con fuente='binance_p2p_buy' y 'binance_p2p_sell'

---

## 14. Configuraciones importantes

### .env crítico en servidor
```env
APP_ENV=production
APP_DEBUG=false
DB_COLLATION=utf8mb4_general_ci  # CRÍTICO: MariaDB 10.3 no soporta utf8mb4_0900_ai_ci
```

### config/database.php
- Collation cambiada a `utf8mb4_general_ci`
- `Schema::defaultStringLength(191)` en AppServiceProvider

### routes/console.php
- Las referencias a `SincronizarTasasJob` están comentadas (el job no existe aún)
- Cuando se implemente Fase 3, descomentar

---

## 15. CI/CD configurado

GitHub Actions corre en cada push a `main`:
1. Conecta al servidor por SSH
2. `git pull origin main`
3. `composer install --no-dev --optimize-autoloader`
4. `php artisan migrate --force`
5. `php artisan config:cache && route:cache && view:cache`
6. Ajusta permisos de storage

Archivo: `.github/workflows/deploy.yml`

---

## 16. Notas para el programador

1. **No usar Laragon/Windows para desarrollo.** Los errores de MySQL con collation y conexiones idle son endémicos. Usar el servidor remoto directamente o Docker con MySQL/MariaDB en Linux.

2. **Probar endpoints con token.** Todos los endpoints excepto login requieren `Authorization: Bearer TOKEN`. El login retorna el token en `{"token": "...", "user": {...}}`.

3. **El rename de ganancia_directa → ganancia_bruta está hecho.** Si ves referencias antiguas en tests o factories, actualizarlas.

4. **FIFO es la parte más compleja.** Requiere diseño cuidadoso antes de implementar. Consultar antes de tocar esa lógica.

5. **Las comisiones son un snapshot.** Una vez aplicadas a una operación, no cambian automáticamente si cambias la configuración. Solo admin puede editarlas manualmente con razón justificada.

6. **El campo `sin_tasa_referencia`** en operaciones indica que se usó la última tasa conocida en lugar de la tasa del día. Estas operaciones deben mostrarse con alerta en el dashboard.
