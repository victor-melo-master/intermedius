# Plan: Flujo Multi-Paso de Operaciones con Transacciones

## Contexto

El cliente pide un flujo donde las operaciones se crean primero como "solicitud" (intención de compra/venta), luego se van agregando y confirmando transacciones individualmente, y finalmente se cierra la operación. También quiere poder cancelar y revertir.

### Lo que ya existe (y se reutiliza)

| Componente | Estado | Se reutiliza |
|---|---|---|
| `Transaccion` model | Existe, pero la tabla `transacciones` no está en el schema | Sí — es el modelo correcto para el flujo nuevo |
| `TransaccionController` | Rutas + controller existentes | Sí — pero hay que modificar las validaciones de estado |
| `TransaccionService` | crear, validar, rechazar, cancelar, cambiar cuentas | Sí — agregar lógica de reversión y snapshot de tasas |
| `Movimiento` model | Tabla existente, usado actualmente | Se mantiene para movimientos legacy/internos |
| `estatus` enum en operaciones | `verificado`, `en_revision`, `sin_verificar` | Se reemplaza por el nuevo flujo de estados |
| `estado_pool` enum | `pendiente`, `asignada`, `pagada`, `cancelada` | Se reemplaza por un solo campo unificado |

---

## 1. Máquina de estados unificada

### Estado actual (dos campos paralelos)

```
estatus:       sin_verificar → en_verificacion → verificado
estado_pool:   pendiente → asignada → pagada / cancelada
```

### Estado propuesto (un solo campo `estado`)

```
solicitud → en_progreso → cerrada
                          ↘ cancelada (en cualquier punto)
```

**Transiciones válidas:**

```
solicitud      → en_progreso    (al agregar primera transacción)
solicitud      → cancelada      (cancelar sin haber iniciado)
en_progreso    → cerrada        (todas las transacciones confirmadas)
en_progreso    → cancelada      (cancelar con transacciones pendientes/confirmadas)
cerrada        → cancelada      (cancelar después de cerrar, con reversión obligatoria)
```

### Campos a modificar en `operaciones`

| Campo actual | Acción | Campo nuevo |
|---|---|---|
| `estatus` (enum) | Reemplazar | `estado` (enum: `solicitud`, `en_progreso`, `cerrada`, `cancelada`) |
| `estado_pool` | Eliminar | Se migra a `estado` |
| `pagador_id` | Mantener | Se usa cuando la operación necesita pago |
| `asignada_at`, `pagada_at` | Mantener | Se usan en el flujo de pool |
| `cancelada_at` | Mantener | Ya existe |
| `motivo_cancelacion` | Mantener | Ya existe |
| `verificado_at` | Renombrar | `cerrada_at` (o mantener y agregar) |
| `verificado_por_id` | Renombrar | `cerrada_por_id` |

### Campos a agregar en `operaciones`

```sql
-- Snapshot de tasas de mercado al momento de la solicitud
tasas_snapshot JSON NULL COMMENT '{"bcv_usd": 685.94, "bcv_usdt": 680.00, "paralelo": 700.00}'

-- Timestamp de cuando pasó a en_progreso
en_progreso_at TIMESTAMP NULL
```

---

## 2. Snapshot de tasas BCV/USDT

### Propuesta: JSON en la operación

En vez de una tabla pivote, usar un campo JSON `tasas_snapshot` en la tabla `operaciones`. Razones:
- Son 3-4 tasas máximo, no justifica una tabla aparte
- Es un snapshot frozen, nunca se modifica después de creado
- Fácil de serializar/deserializar

**Estructura del JSON:**

```json
{
  "bcv_usd": 685.94,
  "bcv_usdt": 680.00,
  "paralelo": 700.00,
  "binance_p2p_buy": 695.00,
  "capturado_en": "2026-07-20T10:30:00Z"
}
```

### En la tabla `transacciones`

Cada transacción también debería tener su propio snapshot de tasas (el cliente lo pide). Agregar:

```sql
-- En tabla transacciones (que aún no existe en schema)
tasas_snapshot JSON NULL COMMENT 'Tasas BCV/USDT al momento de registrar esta transacción'
```

### Flujo de tasas

1. **Al crear solicitud** → el frontend consulta `GET /tasas/actuales` y envía el snapshot en el POST
2. **Al agregar transacción** → el frontend puede enviar un snapshot nuevo (si las tasas cambiaron) o reutilizar el de la operación
3. **Al confirmar transacción** → se guarda el snapshot en la transacción si no lo tiene

---

## 3. Tabla `transacciones` (crear en schema)

La tabla no existe en `mysql.sql`. Crear con este schema:

```sql
CREATE TABLE `transacciones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `operacion_id` bigint(20) unsigned NOT NULL,
  `cuenta_origen_id` bigint(20) unsigned NOT NULL,
  `cuenta_destino_id` bigint(20) unsigned NOT NULL,
  `moneda_id` bigint(20) unsigned NOT NULL,
  `monto` decimal(20,2) NOT NULL,
  `tasa_aplicada` decimal(20,8) DEFAULT NULL COMMENT 'Tasa de cambio aplicada en esta transacción',
  `tasas_snapshot` json DEFAULT NULL COMMENT 'Snapshot de tasas BCV/USDT al momento de la transacción',
  `metodo_pago` varchar(50) DEFAULT NULL COMMENT 'pago_movil, zelle, binance, efectivo, transferencia, otro',
  `comprobante` varchar(500) DEFAULT NULL COMMENT 'Ruta del comprobante en MinIO (obligatorio si metodo_pago != efectivo)',
  `estado` varchar(50) NOT NULL DEFAULT 'pendiente',
  `motivo_rechazo` text DEFAULT NULL,
  `confirmada_en` timestamp NULL DEFAULT NULL,
  `confirmada_por_id` bigint(20) unsigned DEFAULT NULL,
  `orden` smallint(5) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transacciones_operacion_id_foreign` (`operacion_id`),
  KEY `transacciones_cuenta_origen_id_foreign` (`cuenta_origen_id`),
  KEY `transacciones_cuenta_destino_id_foreign` (`cuenta_destino_id`),
  KEY `transacciones_moneda_id_foreign` (`moneda_id`),
  KEY `transacciones_confirmada_por_id_foreign` (`confirmada_por_id`),
  CONSTRAINT `transacciones_operacion_id_foreign` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transacciones_cuenta_origen_id_foreign` FOREIGN KEY (`cuenta_origen_id`) REFERENCES `cuentas` (`id`),
  CONSTRAINT `transacciones_cuenta_destino_id_foreign` FOREIGN KEY (`cuenta_destino_id`) REFERENCES `cuentas` (`id`),
  CONSTRAINT `transacciones_moneda_id_foreign` FOREIGN KEY (`moneda_id`) REFERENCES `monedas` (`id`),
  CONSTRAINT `transacciones_confirmada_por_id_foreign` FOREIGN KEY (`confirmada_por_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Notas:**
- `metodo_pago`: valores posibles: `pago_movil`, `zelle`, `binance`, `efectivo`, `transferencia`, `otro`
- `comprobante`: archivo en MinIO. **Obligatorio** si `metodo_pago != 'efectivo'`, opcional si es efectivo
- `tasa_aplicada`: la tasa de cambio que se usó para esta transacción específica
- `tasas_snapshot`: JSON con las tasas de referencia al momento
- `confirmada_en` / `confirmada_por_id`: reemplaza `validada_en` / `validada_por_id` del modelo actual
- Estados de transacción: `pendiente`, `confirmada`, `rechazada`, `cancelada`, `revertida`

---

## 4. Endpoints propuestos

### 4.1 Crear solicitud (sin transacciones)

```
POST /api/v1/operaciones
```

```json
{
  "fecha": "2026-07-20",
  "tipo_codigo": "venta_usd",
  "cliente_id": 5,
  "operador_id": 1,
  "tasa_aplicada": 420.00,
  "descripcion": "Cliente quiere vender $500",
  "tasas_snapshot": {
    "bcv_usd": 685.94,
    "bcv_usdt": 680.00,
    "paralelo": 700.00
  }
}
```

**Respuesta:** operación en estado `solicitud`, sin movimientos ni transacciones.

**Cambios en `RegistroOperacionService::registrar()`:**
- Hacer `movimientos[]` opcional (si no viene, crear en estado `solicitud`)
- Guardar `tasas_snapshot` en la operación
- Si `tipo_codigo` es de los que requieren movimientos para cuadre, permitir crear sin ellos (el cuadre se valida al cerrar)

### 4.2 Agregar transacción a una solicitud/en_progreso

```
POST /api/v1/operaciones/{operacion}/transacciones
```

```json
{
  "cuenta_origen_id": 10,
  "cuenta_destino_id": 20,
  "moneda_id": 1,
  "monto": 500.00,
  "tasa_aplicada": 420.00,
  "metodo_pago": "pago_movil",
  "tasas_snapshot": {
    "bcv_usd": 686.10,
    "paralelo": 701.00
  },
  "comprobante": "ruta/en/minio/comprobante.pdf"
}
```

**Cambios en `TransaccionController::store()`:**
- Permitir crear cuando `estado` es `solicitud` o `en_progreso` (no solo `en_verificacion`)
- Al crear la primera transacción, cambiar estado de la operación a `en_progreso`
- Guardar `tasa_aplicada` y `tasas_snapshot` en la transacción

### 4.3 Confirmar transacción individual

```
PATCH /api/v1/operaciones/{operacion}/transacciones/{transaccion}/confirmar
```

```json
{
  "comprobante_confirmacion": "ruta/en/minio/comprobante-final.pdf"
}
```

**Cambios en `TransaccionController`:**
- Renombrar `validar` → `confirmar` (o agregar alias)
- Estado de transacción: `pendiente` → `confirmada`
- Registrar `confirmada_en` y `confirmada_por_id`
- Si no tiene `tasas_snapshot`, copiar el de la operación

### 4.4 Cerrar operación

```
POST /api/v1/operaciones/{operacion}/cerrar
```

**Validaciones:**
- Estado debe ser `en_progreso`
- Todas las transacciones deben estar en estado `confirmada`
- Si el tipo de operación requiere cuadre (venta_usd, compra_usd), validar que Σ(monto × tasa) cuadra
- Calcular ganancia bruta final
- Aplicar comisiones
- Actualizar saldos de cuentas afectadas
- Cambiar estado a `cerrada`
- Despachar jobs de FIFO y recálculo de saldos

### 4.5 Cancelar operación con reversión

```
POST /api/v1/operaciones/{operacion}/cancelar
```

```json
{
  "motivo_cancelacion": "Cliente se arrepintió, no va a vender",
  "revertir_transacciones": true
}
```

**Lógica de reversión:**

```
Quién puede cancelar: admin, super_admin, o el operador que creó la solicitud.

Si revertir_transacciones = true:
  Para cada transacción en estado "confirmada":
    1. Crear transacción inversa (origen ↔ destino, monto negativo)
    2. Marcar original como "revertida"
    3. Registrar en la transacción inversa: reversa_de_transaccion_id = original.id
    4. Recalcular saldos de cuentas afectadas
  
  Si hay transacciones "pendientes":
    Marcar como "cancelada"

Si revertir_transacciones = false:
  Solo cambiar estado de la operación a "cancelada"
  Las transacciones quedan en su estado actual (pendiente/confirmada)

Siempre:
  - Registrar motivo_cancelacion
  - Cambiar estado operación a "cancelada"
  - Despachar jobs de recálculo de saldos si hubo reversiones
```

### 4.6 Listar transacciones de una operación

```
GET /api/v1/operaciones/{operacion}/transacciones
```

**Respuesta:** lista de transacciones con estado, comprobante, tasas snapshot, confirmada_por.

### 4.7 Editar transacción pendiente

```
PUT /api/v1/operaciones/{operacion}/transacciones/{transaccion}
```

Solo permitido si la transacción está en estado `pendiente` y la operación está en `solicitud` o `en_progreso`.

### 4.8 Eliminar transacción pendiente

```
DELETE /api/v1/operaciones/{operacion}/transacciones/{transaccion}
```

Solo permitido si la transacción está en estado `pendiente`. Si era la última transacción y la operación queda sin transacciones, volver a estado `solicitud`.

---

## 5. Cambios en el Modelo `Operacion`

### Campos fillable a modificar

```php
protected $fillable = [
    // ... existentes ...
    'estado',           // reemplaza estatus + estado_pool
    'tasas_snapshot',   // JSON con tasas BCV/USDT
    'en_progreso_at',   // timestamp
    // eliminar: estatus, estado_pool
];
```

### Casts a agregar

```php
'tasas_snapshot' => 'array',    // serializa JSON automáticamente
'en_progreso_at' => 'datetime',
```

### Relaciones

Mantener `movimientos()` y `transacciones()` como relaciones separadas. Las operaciones nuevas usan `transacciones`, las viejas usan `movimientos`.

### Scopes

```php
// Renombrar scopes existentes
public function scopeSolicitudes(Builder $query): Builder
{
    return $query->where('estado', 'solicitud');
}

public function scopeEnProgreso(Builder $query): Builder
{
    return $query->where('estado', 'en_progreso');
}

public function scopeCerradas(Builder $query): Builder
{
    return $query->where('estado', 'cerrada');
}

public function scopeCanceladas(Builder $query): Builder
{
    return $query->where('estado', 'cancelada');
}
```

---

## 6. Cambios en el Modelo `Transaccion`

```php
protected $fillable = [
    'operacion_id',
    'cuenta_origen_id',
    'cuenta_destino_id',
    'moneda_id',
    'monto',
    'tasa_aplicada',        // NUEVO
    'tasas_snapshot',        // NUEVO (JSON)
    'metodo_pago',           // NUEVO: pago_movil, zelle, binance, efectivo, transferencia, otro
    'comprobante',
    'estado',
    'motivo_rechazo',
    'confirmada_en',        // renombrado de validada_en
    'confirmada_por_id',    // renombrado de validada_por_id
    'orden',
];

protected function casts(): array
{
    return [
        'monto' => 'decimal:2',
        'tasa_aplicada' => 'decimal:8',
        'tasas_snapshot' => 'array',
        'confirmada_en' => 'datetime',
    ];
}
```

### Estados de transacción

```
pendiente  → confirmada   (al confirmar)
pendiente  → rechazada    (al rechazar)
pendiente  → cancelada    (al cancelar operación sin reversión)
confirmada → revertida    (al cancelar operación con reversión)
```

### Validación de comprobante

```php
// Regla: comprobante obligatorio si metodo_pago != 'efectivo'
if ($metodo_pago !== 'efectivo' && empty($comprobante)) {
    throw ValidationException::withMessages([
        'comprobante' => 'El comprobante es obligatorio para transacciones que no sean en efectivo.',
    ]);
}
```

---

## 7. Cambios en `TransaccionService`

### Métodos a modificar

**`crearTransacciones()`** → agregar parámetro `tasasSnapshot`:
```php
public function crearTransacciones(
    Operacion $operacion,
    array $transaccionesData,
    ?array $tasasSnapshot = null
): Collection
```

**`validarTransaccion()`** → renombrar a `confirmarTransaccion()`:
```php
public function confirmarTransaccion(
    Transaccion $transaccion,
    User $confirmador,
    ?array $tasasSnapshot = null
): Transaccion
```

### Métodos a agregar

**`revertirTransaccion()`**:
```php
public function revertirTransaccion(
    Transaccion $original,
    User $usuario
): Transaccion  // retorna la transacción inversa creada
```

Lógica:
1. Crear nueva transacción con cuenta_origen_id y cuenta_destino_id invertidos
2. Mismo monto, misma moneda
3. `estado = 'revertida'`
4. `reversa_de_transaccion_id = original.id`
5. Marcar original como `revertida`

**`revertirTodasConfirmadas()`**:
```php
public function revertirTodasConfirmadas(
    Operacion $operacion,
    User $usuario
): Collection  // retorna colección de transacciones inversas
```

---

## 8. Cambios en `RegistroOperacionService`

### `registrar()` — hacer movimientos opcionales

```php
// Cambiar validación: si no vienen movimientos, crear en estado solicitud
if (empty($payload['movimientos'])) {
    // Crear operación sin movimientos
    $operacion = Operacion::create([
        // ... campos ...
        'estado' => 'solicitud',
        'tasas_snapshot' => $payload['tasas_snapshot'] ?? null,
    ]);
    return $operacion;
}
```

### Nuevo método: `cerrarOperacion()`

```php
public function cerrarOperacion(Operacion $operacion, User $usuario): Operacion
{
    // 1. Validar que todas las transacciones estén confirmadas
    // 2. Validar cuadre si aplica
    // 3. Calcular ganancia bruta
    // 4. Aplicar comisiones
    // 5. Actualizar saldos de cuentas
    // 6. Cambiar estado a cerrada
    // 7. Despachar jobs
}
```

---

## 9. Migración de datos existentes

Las operaciones existentes tienen `estatus` y `estado_pool`. Migrar:

```sql
-- Mapeo de estados antiguos a nuevos
UPDATE operaciones SET estado = 'cerrada' WHERE estatus = 'verificado';
UPDATE operaciones SET estado = 'en_progreso' WHERE estatus = 'en_verificacion';
UPDATE operaciones SET estado = 'solicitud' WHERE estatus = 'sin_verificar' AND estado_pool != 'cancelada';
UPDATE operaciones SET estado = 'cancelada' WHERE estado_pool = 'cancelada';

-- Mantener estatus y estado_pool como columnas deprecated temporalmente
-- para no romper el código existente durante la transición
```

---

## 10. Orden de implementación

| Fase | Qué | Dependencias |
|---|---|---|
| **1** | Crear tabla `transacciones` en schema | Ninguna |
| **2** | Agregar campos `estado`, `tasas_snapshot`, `en_progreso_at` a `operaciones` | Ninguna |
| **3** | Actualizar modelo `Operacion` (fillable, casts, scopes) | Fase 2 |
| **4** | Actualizar modelo `Transaccion` (fillable, casts) | Fase 1 |
| **5** | Actualizar `TransaccionService` (confirmar, revertir) | Fase 4 |
| **6** | Actualizar `TransaccionController` (store en solicitud/en_progreso, confirmar, cerrar) | Fase 5 |
| **7** | Actualizar `RegistroOperacionService` (crear sin movimientos, cerrarOperacion) | Fase 3 |
| **8** | Agregar endpoint `POST /cerrar` y `POST /cancelar` | Fase 6, 7 |
| **9** | Migrar datos existentes | Fase 2, 3 |
| **10** | Actualizar `PoolController` para usar nuevo campo `estado` | Fase 9 |
| **11** | Tests | Todo |

---

## 11. Diagrama de flujo completo

```
                    ┌─────────────────────┐
                    │   POST /operaciones  │
                    │   (crear solicitud)  │
                    └──────────┬──────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │   estado: solicitud  │
                    │   tasas_snapshot: {} │
                    │   transacciones: []  │
                    └──────────┬──────────┘
                               │
              ┌────────────────┼────────────────┐
              │                │                │
              ▼                ▼                ▼
    POST /transacciones   POST /cancelar    (esperar)
    (agregar transacción)  (cancelar)
              │
              ▼
    ┌─────────────────────┐
    │ estado: en_progreso  │
    │ (se activa al 1er    │
    │  POST /transacciones)│
    └──────────┬──────────┘
               │
    ┌──────────┼──────────┬──────────────┐
    │          │          │              │
    ▼          ▼          ▼              ▼
POST /trans  PATCH /    POST /        POST /
-agregar     confirmar  transaccion/  cancelar
             transacc   confirmar     (con reversión)
    │          │          │              │
    │          ▼          │              │
    │  transacción:       │              │
    │  pendiente→confirmada              │
    │          │          │              │
    │          └────┬─────┘              │
    │               │                    │
    │               ▼                    │
    │  ┌─────────────────────┐           │
    │  │ ¿Todas confirmadas? │           │
    │  └────────┬────────────┘           │
    │           │ SÍ                     │
    │           ▼                        │
    │  POST /operacion/cerrar            │
    │  ┌─────────────────────┐           │
    │  │ estado: cerrada      │           │
    │  │ ganancia calculada   │           │
    │  │ comisiones aplicadas │           │
    │  │ saldos actualizados  │           │
    │  └─────────────────────┘           │
    │                                    │
    └────────────────────────────────────┘
```

---

## 12. Decisiones tomadas

| Decisión | Resolución | Notas |
|---|---|---|
| **Movimientos vs Transacciones** | Mantener ambos | Movimientos para legacy/gastos/ajustes. Transacciones para flujo multi-paso nuevo. |
| **Tasas snapshot** | JSON en operación (forma simple) | Campo `tasas_snapshot` JSON en `operaciones`. Cada transacción también puede tener el suyo propio. |
| **Reversión** | Explícita por transacción Y por operación | Cada transacción confirmada puede revertirse individualmente (transacción inversa). La operación también puede revertir todas las confirmadas de golpe. Ambas acciones quedan trazadas. |
| **Cierre** | Requiere acción explícita ("botón cerrar") | El usuario debe darle a "Cerrar operación" manualmente. |
| **Comprobante** | Obligatorio si metodo_pago != 'efectivo' | Además se registra el tipo de transacción (pago_movil, zelle, binance, etc.) en campo `metodo_pago`. |
| **Quién puede cancelar** | Admin, super_admin, y el operador que creó la solicitud | El operador tiene permiso de cancelar sus propias operaciones. |

Todas las decisiones están resueltas. El plan está listo para implementar.
