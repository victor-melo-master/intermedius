# AGENTS — Intermedius

## Contexto del Proyecto

### Flujo Multi-Paso (Operaciones con Transacciones)

**Rama:** `feat/flujo-multi-paso`  
**Branch base:** `origin/main`

Arquitectura de operaciones en 4 pasos en lugar del CRUD directo de movimientos:

```
solicitud ──[iniciar]──→ en_progreso ──[cerrar]──→ cerrada
     │                      │
     └──[cancelar]──────────┴──[cancelar]──→ cancelada
```

**Backend:**
- `RegistroOperacionService::crearSolicitud()` — crea sin movimientos, guarda `moneda_operacion_id`
- `RegistroOperacionService::iniciarOperacion()` — `solicitud → en_progreso`
- `RegistroOperacionService::cerrarOperacion()` — delega en `CierreOperacionService`
- `RegistroOperacionService::cancelarOperacion()` — revierte saldos si hay confirmadas
- `RegistroOperacionService::crearVenta()` — crea operación venta + cierre atómico (estado `cerrada` directo)
- `RegistroOperacionService::revertirOperacion()` — reversión de ventas cerradas (máx 30 días, motivo requerido)
- `CierreOperacionService` — contiene: `validarComprobantes`, `validarBalance`, `generarMovimientos`, `calcularGanancia`, `cuentasAfectadas`
- `TransaccionController` — CRUD completo de transacciones: store, update, delete, confirmar, revertir, **fallar**
- `TransaccionService::fallarTransaccion()` — marca transacción pendiente como `fallido` con razón
- `SolicitudOperacionRequest` — requiere `moneda_codigo` (antes era implícito USD)
- `VentaOperacionRequest` — valida creación de venta: `moneda_codigo`, `tasa_aplicada`, `cliente_id`, `monto_solicitado`, `transacciones[]`
- `TransaccionResource` — expone `cuenta_origen.alias`, `cuenta_destino.alias`
- Límite de monto excluye transacciones `revertida`/`cancelada`
- PoolController: `index`/`mis-ordenes` filtran solo `compra_usd`; `tomar()` avanza a `en_progreso`

**Base de datos:**
- `operaciones.moneda_operacion_id` (FK → monedas.id, nullable) — qué moneda se negocia (USD/USDT/EUR/COP)
- `transacciones.cliente_id` (FK → clientes.id, nullable) — cliente de la transacción directa
- `clientes.datos_bancarios` (JSON, nullable) — datos bancarios del cliente
- `operaciones.revertida_at` (datetime, nullable) — marca de reversión de venta

**Frontend:**
- Vistas: `OperacionFormView` (nueva/editar), `GestionarOperacionView`, `OperacionDetailView`
- Componentes: `FlujoProgress`, `TransaccionList`, `TransaccionForm`, `ConfirmarTransaccionModal`, `CalculadoraBidireccional`
- Composables: `useOperacionForm`, `useTransacciones`, `useOperaciones` (solicitar/iniciar/cerrar/cancelar/fetchGananciaPreview)
- Store: `operaciones` (solicitar/iniciar/cerrar/cancelar/fetchGananciaPreview)
- `monedaEsUSD` → `esDivisa` (cualquier moneda no-VES funciona como divisa)
- Botón "Agregar transacción" se deshabilita cuando balanceado, muestra "✅ Transacciones balanceadas"
- Botón "Cerrar" deshabilitado hasta balancear, abre modal con campo tasa de mercado
- Preview de ganancia estimada en `GestionarOperacionView` (card + modal de cierre)
- Redirige a `/operaciones` al cerrar
- Fechas en formato `dd/mm/yyyy`
- Direccionalidad compra/venta: compra = casa compra divisa (cliente entrega divisa → casa entrega VES); venta = casa vende divisa (cliente entrega VES → casa entrega divisa)

### Cálculo de Ganancia

**Rama:** `feat/calculo-ganancia`

**Fórmulas (multi-divisa: USD/USDT/EUR/COP):**
- `venta_usd`: `ganancia_ves = monto_divisa × (tasa_aplicada − tasa_mercado)`, `ganancia_usd = ganancia_ves / tasa_aplicada`
- `compra_usd`: `ganancia_ves = monto_divisa × (tasa_mercado − tasa_aplicada)`, `ganancia_usd = ganancia_ves / tasa_mercado`
- `intermediada`: `ganancia_ves = montoDivisa × (tasa_venta − tasa_compra)`, `ganancia_usd = ganancia_ves / tasa_venta`
- `comision`: directa desde `monto_usd_equivalente`
- Netas: `bruta − comisiones` (CalculadorComisionesService)

**Snapshot al cierre:** `tasa_mercado_snapshot` se actualiza al momento de cerrar la operación (no al crear solicitud).

**Preview:** `GET /operaciones/{id}/ganancia-preview?tasa_mercado=X` retorna ganancia estimada sin persistir.

**`genera_ganancia`:** `venta_usd`, `compra_usd`, `intermediada`, `comision` = `true`. Resto = `false`.

### Ajustes Globales (Control General)

Sistema de opciones clave→valor persistidas en la tabla `ajustes` para configurar la app desde un futuro panel de control. El backend ya está completo; falta la vista (ver `PENDIENTES.md`).

**Tabla / Modelo:**
- `ajustes` (clave unique, valor, descripcion) — sembrada en migraciones `2026_08_05_000002_create_ajustes_table` y `2026_08_05_000003_add_envio_emails_ajuste` y en `docker/mysql/00-init.sh`/`seed.sql` (fuente de verdad para BDs frescas).
- `Ajuste::obtener(string $clave, mixed $default)` — valor crudo o default si no existe.
- `Ajuste::activo(string $clave, bool $default = false)` — interpreta el valor como booleano (`filter_var` FILTER_VALIDATE_BOOLEAN; acepta '1', 'true', 'on').

**API:**
- `GET configuracion/ajustes` (autenticado) — lista `[{clave, valor, descripcion}]` ordenados por clave.
- `PATCH configuracion/ajustes` (`role:admin|super_admin`) — body `{ ajustes: { clave: valor } }` o `{ ajustes: [{ clave, valor }] }`; normaliza bool→'1'/'0' y hace `updateOrCreate`.
- Controlador: `api/app/Http/Controllers/Api/V1/Configuracion/AjusteController.php`.

**Ajustes existentes:**
- `password_segura` ('1' por defecto) — en `UserController::reglaPassword()` añade `->uncompromised()` (HIBP) a `Password::min(8)->mixedCase()->numbers()->symbols()`. Si está desactivada, la contraseña se acepta pero la respuesta incluye `advertencias` (via `agregarAdvertenciasPassword` con `Str::isUncompromised`). Mensaje español personalizado: clave `password.uncompromised` en `UserController::mensajesValidacion()` (resuelta por el validador interno de la regla Password).
- `envio_emails` ('1' por defecto) — activa/desactiva correos. Guardas en 4 puntos: `UserController::store`, `User::sendEmailVerificationNotification()`, `AlertarTasasFaltantesJob` (solo registra en log si está desactivado), `GenerarReporteMensualComisionesJob` (conjunto con `config('reportes.comisiones_operadores.enviar_email')`).

**Frontend (`UsuariosView.vue`):**
- Botón "Generar contraseña segura": 16 caracteres (4 clases garantizadas + 12 aleatorios) con `crypto.getRandomValues` sin sesgo de módulo (`indiceAleatorio`) y barajado Fisher-Yates; la muestra en claro para copiarla.
- Validación de unicidad en vivo de `name`/`email` contra `GET /usuarios/disponible?campo=&valor=&exclude_id=`.
- Toast ámbar que muestra `res.advertencias` (contraseña comprometida con `password_segura` off); timer limpiado en `onBeforeUnmount`.
- Requisitos de contraseña en vivo (`passwordRequisitos`) alineados con la regla del backend (min 8, mixta, número, símbolo).
- `name`/`email` se normalizan a minúsculas: mutators en `User` + migración `2026_08_05_000001_normalize_user_name_email_lowercase`.

### Perfil de Usuario Autogestionado

**Rama:** `feat/perfil-usuario`

Vista donde cualquier usuario autenticado modifica su correo, teléfono, foto de perfil y contraseña. El rol/tipo de usuario es solo lectura.

**Backend:**
- `users.telefono` (string, nullable) — teléfono de contacto; migración `2026_08_06_000001_add_telefono_to_users_table`. Sin migración en seeds (solo DDL).
- `UserController::perfil()` — `GET perfil` (autenticado) → `formatUser($request->user())`.
- `UserController::perfilUpdate()` — `PATCH perfil` (autenticado): valida `email` (unique, ignora al propio), `telefono` (nullable), `avatar` (imagen→webp) y `password` (`confirmed` + `reglaPassword()`). Cambiar correo o contraseña **exige `password_actual`** (`Hash::check`); si el correo cambia, `email_verified_at` se pone en `null` y se reenvía `VerifyEmailNotification` (respeta `envio_emails`). El `rol` no se acepta ni se toca.
- Rutas en `routes/api.php`: `GET/PATCH api/v1/perfil` (auth:sanctum, sin role).
- `AuthController::usuarioConRol()` ahora incluye `telefono` y `avatar_path` (login y `me`).
- Tests en `UserEndpointTest` (sección perfil): teléfono sin password, email exige password, password actual incorrecta, cambio de password, email único, rol inmutable.

**Frontend:**
- `PerfilView.vue` (`/perfil`, cualquier rol) — tarjetas: Datos de perfil (avatar con preview/validación tipo y 2MB, nombre readonly, correo, teléfono), Tipo de usuario (badges readonly), Cambiar contraseña (requisitos en vivo + mostrar/ocultar).
- Header (`AppShell.vue`): avatar + enlace al perfil (`avatarUrl()` con `?token=`).
- `auth.actualizarUsuario(u)` en `stores/auth.js` para refrescar el store tras guardar.

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
