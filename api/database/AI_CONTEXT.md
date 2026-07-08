# Database — AI Context

## Convenciones de naming
- Nombres de tablas en **snake_case** y **plural**: `bancos`, `clientes`, `tipos_operacion`, `comisiones_operacion`
- Columnas en **snake_case**: `tasa_compra`, `monto_usd_equivalente`, `vigente_desde`
- Llave primaria siempre: `id` (bigint unsigned, AUTO_INCREMENT)
- Timestamps audit: `created_at`, `updated_at` (timestamp, nullable)
- Soft delete opcional: `deleted_at` (timestamp, nullable)
- FK columns con nombre `{tabla}_id` (ej: `cliente_id`, `moneda_id`, `operacion_id`)
- Tabla pivote Spatie: `model_has_roles`, `model_has_permissions`, `role_has_permissions`

---

## Esquema completo

### Tabla: `activity_log`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| log_name | varchar(255) | YES | NULL | — |
| description | text | NO | — | — |
| subject_type | varchar(255) | YES | NULL | — |
| event | varchar(255) | YES | NULL | — |
| subject_id | bigint(20) unsigned | YES | NULL | — |
| causer_type | varchar(255) | YES | NULL | — |
| causer_id | bigint(20) unsigned | YES | NULL | — |
| properties | longtext (utf8mb4_bin) | YES | NULL | — |
| batch_uuid | char(36) | YES | NULL | — |
| created_at | timestamp | YES | NULL | — |
| updated_at | timestamp | YES | NULL | — |
| **Índices**: `subject` (subject_type, subject_id), `causer` (causer_type, causer_id), `activity_log_log_name_index` (log_name) |

---

### Tabla: `bancos`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| nombre | varchar(255) | NO | — | — |
| codigo | varchar(255) | YES | NULL | — |
| pais | char(2) | NO | 'VE' | — |
| activo | tinyint(1) | NO | 1 | — |
| created_at | timestamp | YES | NULL | — |
| updated_at | timestamp | YES | NULL | — |
| **Únicos**: `bancos_nombre_unique` (nombre) |

---

### Tabla: `cache`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| key | varchar(255) | NO | — | — |
| value | mediumtext | NO | — | — |
| expiration | int(11) | NO | — | — |
| PK: `key` |

---

### Tabla: `cache_locks`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| key | varchar(255) | NO | — | — |
| owner | varchar(255) | NO | — | — |
| expiration | int(11) | NO | — | — |
| PK: `key` |

---

### Tabla: `categorias_gasto`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| nombre | varchar(255) | NO | — | — |
| titular_id | bigint(20) unsigned | YES | NULL | → `titulares.id` (ON DELETE SET NULL) |
| activa | tinyint(1) | NO | 1 | — |
| created_at | timestamp | YES | NULL | — |
| updated_at | timestamp | YES | NULL | — |
| **Únicos**: `categorias_gasto_nombre_unique` (nombre) |

---

### Tabla: `clientes`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| nombre | varchar(255) | NO | — | — |
| alias | varchar(255) | YES | NULL | — |
| documento | varchar(255) | YES | NULL | — |
| telefono | varchar(255) | YES | NULL | — |
| email | varchar(255) | YES | NULL | — |
| notas | text | YES | NULL | — |
| saldo_cache_usd | decimal(20,4) | NO | 0.0000 | — |
| saldo_cache_at | timestamp | YES | NULL | — |
| activo | tinyint(1) | NO | 1 | — |
| created_at | timestamp | YES | NULL | — |
| updated_at | timestamp | YES | NULL | — |
| deleted_at | timestamp | YES | NULL | — |
| **Índices**: FULLTEXT `clientes_nombre_alias_fulltext` (nombre, alias) |

---

### Tabla: `comisiones_cuenta`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| cuenta_id | bigint(20) unsigned | YES | NULL | → `cuentas.id` (ON UPDATE CASCADE) |
| banco_id | bigint(20) unsigned | YES | NULL | → `bancos.id` (ON UPDATE CASCADE) |
| descripcion | varchar(100) | NO | — | — |
| tipo_calculo | enum('porcentaje','monto_fijo') | NO | — | — |
| valor | decimal(20,8) | NO | — | — |
| moneda_id | bigint(20) unsigned | NO | — | → `monedas.id` |
| aplica_a | enum('ingreso','egreso','ambos') | NO | — | — |
| vigente_desde | date | NO | — | — |
| vigente_hasta | date | YES | NULL | — |
| activa | tinyint(1) | NO | 1 | — |
| created_at | timestamp | YES | NULL | — |
| updated_at | timestamp | YES | NULL | — |
| **Índices**: `comisiones_cuenta_cuenta_id_activa_index` (cuenta_id, activa), `comisiones_cuenta_banco_id_activa_index` (banco_id, activa), `comisiones_cuenta_vigente_desde_vigente_hasta_index` (vigente_desde, vigente_hasta) |

---

### Tabla: `comisiones_metodo_pago`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| nombre_metodo | varchar(80) | NO | — | — |
| cuenta_id | bigint(20) unsigned | YES | NULL | → `cuentas.id` (ON UPDATE CASCADE) |
| descripcion | varchar(100) | NO | — | — |
| tipo_calculo | enum('porcentaje','monto_fijo') | NO | — | — |
| valor | decimal(20,8) | NO | — | — |
| moneda_id | bigint(20) unsigned | NO | — | → `monedas.id` |
| vigente_desde | date | NO | — | — |
| vigente_hasta | date | YES | NULL | — |
| activa | tinyint(1) | NO | 1 | — |
| created_at | timestamp | YES | NULL | — |
| updated_at | timestamp | YES | NULL | — |
| **Índices**: `comisiones_metodo_pago_nombre_metodo_activa_index` (nombre_metodo, activa), `comisiones_metodo_pago_cuenta_id_activa_index` (cuenta_id, activa) |

---

### Tabla: `comisiones_operacion`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| operacion_id | bigint(20) unsigned | NO | — | → `operaciones.id` (ON DELETE CASCADE, ON UPDATE CASCADE) |
| tipo | enum('cuenta','operador','metodo_pago','manual') | NO | — | — |
| origen_type | varchar(255) | YES | NULL | — |
| origen_id | bigint(20) unsigned | YES | NULL | — |
| descripcion | varchar(200) | NO | — | — |
| monto | decimal(20,4) | NO | — | — |
| moneda_id | bigint(20) unsigned | NO | — | → `monedas.id` |
| monto_usd_equivalente | decimal(20,4) | NO | — | — |
| movimiento_id | bigint(20) unsigned | YES | NULL | → `movimientos.id` (ON DELETE SET NULL, ON UPDATE CASCADE) |
| editada_por_id | bigint(20) unsigned | YES | NULL | → `users.id` (ON DELETE SET NULL) |
| editada_at | timestamp | YES | NULL | — |
| razon_edicion | text | YES | NULL | — |
| created_at | timestamp | YES | NULL | — |
| updated_at | timestamp | YES | NULL | — |
| **Índices**: `idx_comision_origen` (origen_type, origen_id), `comisiones_operacion_operacion_id_tipo_index` (operacion_id, tipo) |

---

### Tabla: `comisiones_operador`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| titular_id | bigint(20) unsigned | NO | — | → `titulares.id` (ON UPDATE CASCADE) |
| tipo_operacion_id | bigint(20) unsigned | YES | NULL | → `tipos_operacion.id` (ON UPDATE CASCADE) |
| descripcion | varchar(100) | NO | — | — |
| tipo_calculo | enum('porcentaje','monto_fijo') | NO | — | — |
| valor | decimal(20,8) | NO | — | — |
| moneda_id | bigint(20) unsigned | NO | — | → `monedas.id` |
| base_calculo | enum('monto_operacion','ganancia_bruta') | NO | 'monto_operacion' | — |
| vigente_desde | date | NO | — | — |
| vigente_hasta | date | YES | NULL | — |
| activa | tinyint(1) | NO | 1 | — |
| created_at | timestamp | YES | NULL | — |
| updated_at | timestamp | YES | NULL | — |
| **Índices**: `comisiones_operador_titular_id_activa_index` (titular_id, activa), `comisiones_operador_vigente_desde_vigente_hasta_index` (vigente_desde, vigente_hasta) |

---

### Tabla: `cuentas`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| titular_id | bigint(20) unsigned | YES | NULL | → `titulares.id` |
| cliente_id | bigint(20) unsigned | YES | NULL | → `clientes.id` (ON DELETE SET NULL, ON UPDATE CASCADE) |
| banco_id | bigint(20) unsigned | YES | NULL | → `bancos.id` (ON DELETE SET NULL) |
| moneda_id | bigint(20) unsigned | NO | — | → `monedas.id` |
| alias | varchar(255) | NO | — | — |
| tipo | enum('banco','plataforma','cash','wallet','zelle','efectivo','otro') | NO | — | — |
| numero_cuenta | varchar(255) | YES | NULL | — |
| saldo_cache | decimal(20,4) | NO | 0.0000 | — |
| saldo_cache_at | timestamp | YES | NULL | — |
| activa | tinyint(1) | NO | 1 | — |
| notas | text | YES | NULL | — |
| created_at | timestamp | YES | NULL | — |
| updated_at | timestamp | YES | NULL | — |
| deleted_at | timestamp | YES | NULL | — |
| **Únicos**: `cuentas_titular_banco_alias_unique` (titular_id, banco_id, alias), `cuentas_cliente_banco_alias_unique` (cliente_id, banco_id, alias) |

---

### Tabla: `failed_jobs`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| uuid | varchar(255) | NO | — | — |
| connection | text | NO | — | — |
| queue | text | NO | — | — |
| payload | longtext | NO | — | — |
| exception | longtext | NO | — | — |
| failed_at | timestamp | NO | current_timestamp() | — |
| **Únicos**: `failed_jobs_uuid_unique` (uuid) |

---

### Tabla: `job_batches`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | varchar(255) | NO | — | — |
| name | varchar(255) | NO | — | — |
| total_jobs | int(11) | NO | — | — |
| pending_jobs | int(11) | NO | — | — |
| failed_jobs | int(11) | NO | — | — |
| failed_job_ids | longtext | NO | — | — |
| options | mediumtext | YES | NULL | — |
| cancelled_at | int(11) | YES | NULL | — |
| created_at | int(11) | NO | — | — |
| finished_at | int(11) | YES | NULL | — |

---

### Tabla: `jobs`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| queue | varchar(255) | NO | — | — |
| payload | longtext | NO | — | — |
| attempts | tinyint(3) unsigned | NO | — | — |
| reserved_at | int(10) unsigned | YES | NULL | — |
| available_at | int(10) unsigned | NO | — | — |
| created_at | int(10) unsigned | NO | — | — |
| **Índices**: `jobs_queue_index` (queue) |

---

### Tabla: `migrations`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | int(10) unsigned | NO | AUTO_INCREMENT | — |
| migration | varchar(255) | NO | — | — |
| batch | int(11) | NO | — | — |

---

### Tabla: `model_has_permissions`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| permission_id | bigint(20) unsigned | NO | — | → `permissions.id` (ON DELETE CASCADE) |
| model_type | varchar(255) | NO | — | — |
| model_id | bigint(20) unsigned | NO | — | — |
| PK compuesta: (permission_id, model_id, model_type) |
| **Índices**: `model_has_permissions_model_id_model_type_index` (model_id, model_type) |

---

### Tabla: `model_has_roles`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| role_id | bigint(20) unsigned | NO | — | → `roles.id` (ON DELETE CASCADE) |
| model_type | varchar(255) | NO | — | — |
| model_id | bigint(20) unsigned | NO | — | — |
| PK compuesta: (role_id, model_id, model_type) |
| **Índices**: `model_has_roles_model_id_model_type_index` (model_id, model_type) |

---

### Tabla: `monedas`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| codigo | varchar(10) | NO | — | — |
| nombre | varchar(255) | NO | — | — |
| simbolo | varchar(10) | YES | NULL | — |
| es_fiat | tinyint(1) | NO | 1 | — |
| es_cripto | tinyint(1) | NO | 0 | — |
| decimales | tinyint(4) | NO | 2 | — |
| activa | tinyint(1) | NO | 1 | — |
| created_at | timestamp | YES | NULL | — |
| updated_at | timestamp | YES | NULL | — |
| **Únicos**: `monedas_codigo_unique` (codigo) |

---

### Tabla: `movimientos`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| operacion_id | bigint(20) unsigned | NO | — | → `operaciones.id` (ON DELETE CASCADE, ON UPDATE CASCADE) |
| cuenta_id | bigint(20) unsigned | NO | — | → `cuentas.id` (ON UPDATE CASCADE) |
| moneda_id | bigint(20) unsigned | NO | — | → `monedas.id` (ON UPDATE CASCADE) |
| monto | decimal(20,4) | NO | — | — |
| tasa_a_usd | decimal(20,8) | NO | — | — |
| monto_usd_equivalente | decimal(20,4) | NO | — | — |
| orden | smallint(5) unsigned | NO | 1 | — |
| created_at | timestamp | YES | NULL | — |
| updated_at | timestamp | YES | NULL | — |
| **Índices**: `movimientos_cuenta_id_created_at_index` (cuenta_id, created_at), `movimientos_operacion_id_orden_index` (operacion_id, orden), `movimientos_cuenta_id_moneda_id_index` (cuenta_id, moneda_id) |

---

### Tabla: `operaciones`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| fecha | date | NO | — | — |
| tipo_operacion_id | bigint(20) unsigned | NO | — | → `tipos_operacion.id` (ON UPDATE CASCADE) |
| cliente_id | bigint(20) unsigned | YES | NULL | → `clientes.id` (ON UPDATE CASCADE) |
| cliente_emisor_id | bigint(20) unsigned | YES | NULL | — |
| cliente_receptor_id | bigint(20) unsigned | YES | NULL | — |
| categoria_gasto_id | bigint(20) unsigned | YES | NULL | → `categorias_gasto.id` (ON UPDATE CASCADE) |
| operador_id | bigint(20) unsigned | NO | — | → `users.id` (ON UPDATE CASCADE) |
| tasa_aplicada | decimal(20,8) | YES | NULL | — |
| tasa_compra | decimal(20,8) | YES | NULL | — |
| tasa_venta | decimal(20,8) | YES | NULL | — |
| genera_comision | tinyint(1) | NO | 0 | — |
| monto_comision | decimal(20,4) | NO | 0.0000 | — |
| tipo_comision | varchar(50) | YES | NULL | — |
| tasa_sugerida | decimal(20,8) | YES | NULL | — |
| tasa_diaria_id | bigint(20) unsigned | YES | NULL | → `tasas_diarias.id` (ON DELETE SET NULL, ON UPDATE CASCADE) |
| sin_tasa_referencia | tinyint(1) | NO | 0 | — |
| tasa_mercado_snapshot | decimal(20,8) | YES | NULL | — |
| fuente_tasa_mercado | varchar(30) | YES | NULL | — |
| ganancia_bruta_usd | decimal(20,4) | NO | 0.0000 | — |
| ganancia_real_usd | decimal(20,4) | YES | NULL | — |
| ganancia_bruta_ves | decimal(20,2) | NO | 0.00 | — |
| ganancia_real_ves | decimal(20,2) | YES | NULL | — |
| total_comisiones_usd | decimal(20,4) | NO | 0.0000 | — |
| total_comisiones_ves | decimal(20,2) | NO | 0.00 | — |
| ganancia_neta_usd | decimal(20,4) | NO | 0.0000 | — |
| ganancia_neta_ves | decimal(20,2) | NO | 0.00 | — |
| referencia | varchar(100) | YES | NULL | — |
| descripcion | text | YES | NULL | — |
| estatus | enum('verificado','en_revision','sin_verificar') | NO | 'sin_verificar' | — |
| estado_pool | enum('pendiente','asignada','pagada','cancelada') | NO | 'pendiente' | — |
| pagador_id | bigint(20) unsigned | YES | NULL | → `users.id` (ON DELETE SET NULL) |
| asignada_at | timestamp | YES | NULL | — |
| pagada_at | timestamp | YES | NULL | — |
| cancelada_at | timestamp | YES | NULL | — |
| motivo_cancelacion | text | YES | NULL | — |
| verificado_at | timestamp | YES | NULL | — |
| verificado_por_id | bigint(20) unsigned | YES | NULL | → `users.id` (ON DELETE SET NULL) |
| origen | enum('manual','importado','ajuste_apertura') | NO | 'manual' | — |
| origen_referencia | varchar(100) | YES | NULL | — |
| created_at | timestamp | YES | NULL | — |
| updated_at | timestamp | YES | NULL | — |
| deleted_at | timestamp | YES | NULL | — |
| **Únicos**: `operaciones_origen_referencia_unique` (origen_referencia) |
| **Índices**: `operaciones_fecha_tipo_operacion_id_index` (fecha, tipo_operacion_id), `operaciones_estatus_index` (estatus), `operaciones_cliente_id_index` (cliente_id), `operaciones_operador_id_index` (operador_id) |

---

### Tabla: `password_reset_tokens`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| email | varchar(255) | NO | — | — |
| token | varchar(255) | NO | — | — |
| created_at | timestamp | YES | NULL | — |
| PK: `email` |

---

### Tabla: `permissions`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| name | varchar(255) | NO | — | — |
| guard_name | varchar(255) | NO | — | — |
| created_at | timestamp | YES | NULL | — |
| updated_at | timestamp | YES | NULL | — |
| **Únicos**: `permissions_name_guard_name_unique` (name, guard_name) |

---

### Tabla: `personal_access_tokens`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| tokenable_type | varchar(255) | NO | — | — |
| tokenable_id | bigint(20) unsigned | NO | — | — |
| name | text | NO | — | — |
| token | varchar(64) | NO | — | — |
| abilities | text | YES | NULL | — |
| last_used_at | timestamp | YES | NULL | — |
| expires_at | timestamp | YES | NULL | — |
| created_at | timestamp | YES | NULL | — |
| updated_at | timestamp | YES | NULL | — |
| **Únicos**: `personal_access_tokens_token_unique` (token) |
| **Índices**: `personal_access_tokens_tokenable_type_tokenable_id_index` (tokenable_type, tokenable_id), `personal_access_tokens_expires_at_index` (expires_at) |

---

### Tabla: `role_has_permissions`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| permission_id | bigint(20) unsigned | NO | — | → `permissions.id` (ON DELETE CASCADE) |
| role_id | bigint(20) unsigned | NO | — | → `roles.id` (ON DELETE CASCADE) |
| PK compuesta: (permission_id, role_id) |

---

### Tabla: `roles`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| name | varchar(255) | NO | — | — |
| guard_name | varchar(255) | NO | — | — |
| created_at | timestamp | YES | NULL | — |
| updated_at | timestamp | YES | NULL | — |
| **Únicos**: `roles_name_guard_name_unique` (name, guard_name) |

---

### Tabla: `sessions`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | varchar(255) | NO | — | — |
| user_id | bigint(20) unsigned | YES | NULL | — |
| ip_address | varchar(45) | YES | NULL | — |
| user_agent | text | YES | NULL | — |
| payload | longtext | NO | — | — |
| last_activity | int(11) | NO | — | — |
| **Índices**: `sessions_user_id_index` (user_id), `sessions_last_activity_index` (last_activity) |

---

### Tabla: `tasas_diarias`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| fecha | date | NO | — | — |
| moneda_base_id | bigint(20) unsigned | NO | — | → `monedas.id` (ON UPDATE CASCADE) |
| moneda_cotizada_id | bigint(20) unsigned | NO | — | → `monedas.id` (ON UPDATE CASCADE) |
| tasa_compra | decimal(20,8) | NO | — | — |
| tasa_compra_minima | decimal(20,8) | YES | NULL | — |
| tasa_venta | decimal(20,8) | NO | — | — |
| tasa_venta_minima | decimal(20,8) | YES | NULL | — |
| definida_por_id | bigint(20) unsigned | NO | — | → `users.id` (ON UPDATE CASCADE) |
| notas | text | YES | NULL | — |
| vigente_desde | timestamp | NO | CURRENT_TIMESTAMP | — |
| vigente_hasta | timestamp | YES | NULL | — |
| created_at | timestamp | YES | NULL | — |
| updated_at | timestamp | YES | NULL | — |
| **Índices**: `idx_tasa_dia_par` (fecha, moneda_base_id, moneda_cotizada_id), `idx_tasa_vigencia` (moneda_base_id, moneda_cotizada_id, vigente_desde, vigente_hasta) |

---

### Tabla: `tasas_mercado`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| fuente | varchar(30) | NO | — | — |
| moneda_base_id | bigint(20) unsigned | NO | — | → `monedas.id` (ON UPDATE CASCADE) |
| moneda_cotizada_id | bigint(20) unsigned | NO | — | → `monedas.id` (ON UPDATE CASCADE) |
| valor | decimal(20,8) | NO | — | — |
| capturado_en | timestamp | NO | CURRENT_TIMESTAMP | — |
| payload_original | longtext (utf8mb4_bin) | YES | NULL | — |
| created_at | timestamp | YES | NULL | — |
| updated_at | timestamp | YES | NULL | — |
| **Índices**: `idx_tasas_fuente_capturado` (fuente, capturado_en), `idx_tasas_par_capturado` (moneda_base_id, moneda_cotizada_id, capturado_en), `tasas_mercado_capturado_en_index` (capturado_en) |

---

### Tabla: `tipos_operacion`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| codigo | varchar(30) | NO | — | — |
| nombre | varchar(255) | NO | — | — |
| afecta_cliente | tinyint(1) | NO | 0 | — |
| afecta_fifo | tinyint(1) | NO | 0 | — |
| genera_ganancia | tinyint(1) | NO | 0 | — |
| activo | tinyint(1) | NO | 1 | — |
| created_at | timestamp | YES | NULL | — |
| updated_at | timestamp | YES | NULL | — |
| **Únicos**: `tipos_operacion_codigo_unique` (codigo) |

---

### Tabla: `titulares`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| nombre | varchar(255) | NO | — | — |
| alias | varchar(255) | YES | NULL | — |
| activo | tinyint(1) | NO | 1 | — |
| created_at | timestamp | YES | NULL | — |
| updated_at | timestamp | YES | NULL | — |
| deleted_at | timestamp | YES | NULL | — |
| **Únicos**: `titulares_nombre_unique` (nombre) |

---

### Tabla: `users`
| Columna | Tipo | Nullable | Default | FK |
|---|---|---|---|---|
| id | bigint(20) unsigned | NO | AUTO_INCREMENT | — |
| titular_id | bigint(20) unsigned | YES | NULL | → `titulares.id` (ON DELETE SET NULL) |
| name | varchar(255) | NO | — | — |
| email | varchar(255) | NO | — | — |
| email_verified_at | timestamp | YES | NULL | — |
| password | varchar(255) | NO | — | — |
| remember_token | varchar(100) | YES | NULL | — |
| activo | tinyint(1) | NO | 1 | — |
| last_login_at | timestamp | YES | NULL | — |
| created_at | timestamp | YES | NULL | — |
| updated_at | timestamp | YES | NULL | — |
| deleted_at | timestamp | YES | NULL | — |
| **Únicos**: `users_email_unique` (email) |

---

## Relaciones entre tablas (Foreign Keys)

| FK | Origen → Destino | Comportamiento |
|---|---|---|
| categorias_gasto.titular_id | → titulares.id | ON DELETE SET NULL |
| comisiones_cuenta.cuenta_id | → cuentas.id | ON UPDATE CASCADE |
| comisiones_cuenta.banco_id | → bancos.id | ON UPDATE CASCADE |
| comisiones_cuenta.moneda_id | → monedas.id | — |
| comisiones_metodo_pago.cuenta_id | → cuentas.id | ON UPDATE CASCADE |
| comisiones_metodo_pago.moneda_id | → monedas.id | — |
| comisiones_operacion.operacion_id | → operaciones.id | ON DELETE CASCADE, ON UPDATE CASCADE |
| comisiones_operacion.moneda_id | → monedas.id | — |
| comisiones_operacion.movimiento_id | → movimientos.id | ON DELETE SET NULL, ON UPDATE CASCADE |
| comisiones_operacion.editada_por_id | → users.id | ON DELETE SET NULL |
| comisiones_operador.titular_id | → titulares.id | ON UPDATE CASCADE |
| comisiones_operador.tipo_operacion_id | → tipos_operacion.id | ON UPDATE CASCADE |
| comisiones_operador.moneda_id | → monedas.id | — |
| cuentas.titular_id | → titulares.id | — |
| cuentas.cliente_id | → clientes.id | ON DELETE SET NULL, ON UPDATE CASCADE |
| cuentas.banco_id | → bancos.id | ON DELETE SET NULL |
| cuentas.moneda_id | → monedas.id | — |
| model_has_permissions.permission_id | → permissions.id | ON DELETE CASCADE |
| model_has_roles.role_id | → roles.id | ON DELETE CASCADE |
| movimientos.operacion_id | → operaciones.id | ON DELETE CASCADE, ON UPDATE CASCADE |
| movimientos.cuenta_id | → cuentas.id | ON UPDATE CASCADE |
| movimientos.moneda_id | → monedas.id | ON UPDATE CASCADE |
| operaciones.tipo_operacion_id | → tipos_operacion.id | ON UPDATE CASCADE |
| operaciones.cliente_id | → clientes.id | ON UPDATE CASCADE |
| operaciones.categoria_gasto_id | → categorias_gasto.id | ON UPDATE CASCADE |
| operaciones.operador_id | → users.id | ON UPDATE CASCADE |
| operaciones.tasa_diaria_id | → tasas_diarias.id | ON DELETE SET NULL, ON UPDATE CASCADE |
| operaciones.pagador_id | → users.id | ON DELETE SET NULL |
| operaciones.verificado_por_id | → users.id | ON DELETE SET NULL |
| role_has_permissions.permission_id | → permissions.id | ON DELETE CASCADE |
| role_has_permissions.role_id | → roles.id | ON DELETE CASCADE |
| tasas_diarias.moneda_base_id | → monedas.id | ON UPDATE CASCADE |
| tasas_diarias.moneda_cotizada_id | → monedas.id | ON UPDATE CASCADE |
| tasas_diarias.definida_por_id | → users.id | ON UPDATE CASCADE |
| tasas_mercado.moneda_base_id | → monedas.id | ON UPDATE CASCADE |
| tasas_mercado.moneda_cotizada_id | → monedas.id | ON UPDATE CASCADE |
| users.titular_id | → titulares.id | ON DELETE SET NULL |

---

## Índices únicos y compuestos relevantes

| Tabla | Columnas | Tipo |
|---|---|---|
| bancos | nombre | ÚNICO |
| categorias_gasto | nombre | ÚNICO |
| cuentas | (titular_id, banco_id, alias) | ÚNICO compuesto |
| cuentas | (cliente_id, banco_id, alias) | ÚNICO compuesto |
| monedas | codigo | ÚNICO |
| operaciones | origen_referencia | ÚNICO |
| permissions | (name, guard_name) | ÚNICO compuesto |
| personal_access_tokens | token | ÚNICO |
| roles | (name, guard_name) | ÚNICO compuesto |
| tipos_operacion | codigo | ÚNICO |
| titulares | nombre | ÚNICO |
| users | email | ÚNICO |
| failed_jobs | uuid | ÚNICO |

---

## Seeders

### `DatabaseSeeder`
Orquesta la ejecución en orden:
1. `CatalogosBaseSeeder` (catálogos base)
2. `AdminUserSeeder` (usuario admin)

### `CatalogosBaseSeeder`
Crea los datos base del sistema usando `firstOrCreate` (idempotente):

**Roles (Spatie Permission):**
| Role | Guard |
|---|---|
| super_admin | web |
| admin | web |
| operador | web |
| contador | web |
| lectura | web |
| pagador | web |

**Monedas:**
| Código | Nombre | Símbolo | Fiat | Crypto | Decimales |
|---|---|---|---|---|---|
| VES | Bolívar Venezolano | Bs. | Sí | No | 2 |
| USD | Dólar Estadounidense | $ | Sí | No | 2 |
| USDT | Tether USD | ₮ | No | Sí | 6 |
| EUR | Euro | € | Sí | No | 2 |
| COP | Peso Colombiano | $ | Sí | No | 2 |

**Bancos:**
| Nombre | Código | País |
|---|---|---|
| Banesco | 0134 | VE |
| Mercantil | 0105 | VE |
| Venezuela | 0102 | VE |
| Provincial | 0108 | VE |
| Bancamiga | 0172 | VE |
| Tesoro | 0163 | VE |
| Bancaribe | 0114 | VE |
| Banesco Panamá | NULL | PA |
| Mercantil Panamá | NULL | PA |
| Bancolombia | NULL | CO |
| Truist Bank | NULL | US |
| Bank of America | NULL | US |
| Banco 53 | NULL | PA |

**Tipos de Operación:**
| Código | Nombre | Afecta Cliente | Afecta FIFO | Genera Ganancia |
|---|---|---|---|---|
| venta_usd | Venta de USD | Sí | Sí | Sí |
| compra_usd | Compra de USD | Sí | Sí | No |
| cambio | Cambio de moneda | No | No | No |
| gasto | Gasto operativo | No | No | No |
| comision | Comisión | Sí | No | Sí |
| traslado | Traslado interno | No | No | No |
| ajuste | Ajuste contable | No | No | No |
| ajuste_apertura | Ajuste de apertura | No | Sí | No |

### `AdminUserSeeder`
Crea un usuario administrador por defecto (idempotente con `firstOrCreate`):

- **Email:** `admin@test.com`
- **Name:** `Admin Principal`
- **Password:** `password123` (bcrypt)
- **Activo:** true
- **Rol asignado:** `super_admin` (vía `assignRole`)

---

## `init.sql`

Script de inicialización alternativo (SQL plano, no usa Eloquent). Crea los mismos datos que los seeders, con diferencias:

- **Tipos de operación:** incluye adicionalmente `intermediada` (Operación Intermediada, afecta_cliente=1, afecta_fifo=1, genera_ganancia=1). Total: 9 registros vs 8 del seeder.
- **Titular genérico:** inserta `Pago a terceros` (alias: `terceros`, activo=1).
- Usuario admin creado con hash bcrypt directo y asignación de rol vía INSERT en `model_has_roles`.
- Usa `INSERT ... ON DUPLICATE KEY UPDATE` para ser idempotente.

---

## Mapa de dominio (resumen entidades core)

```
titulares ──┬── cuentas (banco/plataforma/wallet/cash/efectivo/zelle/otro)
            ├── users (operadores/administradores)
            ├── categorias_gasto
            └── comisiones_operador

clientes ───┬── cuentas
            └── operaciones

bancos ─────┬── cuentas
            ├── comisiones_cuenta
            └── comisiones_metodo_pago

monedas ────┬── cuentas
            ├── movimientos
            ├── tasas_diarias
            ├── tasas_mercado
            ├── comisiones_*
            └── operaciones (vía tasas)

operaciones ──┬── movimientos (ingresos/egresos de cada op)
              ├── comisiones_operacion
              └── tipos_operacion (clasificación)

tipos_operacion ──── comisiones_operador
```
