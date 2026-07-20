CREATE TABLE `activity_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `event` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `causer_type` varchar(255) DEFAULT NULL,
  `causer_id` bigint(20) unsigned DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `batch_uuid` char(36) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `bancos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `codigo` varchar(255) DEFAULT NULL,
  `pais` char(2) NOT NULL DEFAULT 'VE',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bancos_nombre_unique` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `categorias_gasto` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `titular_id` bigint(20) unsigned DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categorias_gasto_nombre_unique` (`nombre`),
  KEY `categorias_gasto_titular_id_foreign` (`titular_id`),
  CONSTRAINT `categorias_gasto_titular_id_foreign` FOREIGN KEY (`titular_id`) REFERENCES `titulares` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `clientes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `alias` varchar(255) DEFAULT NULL,
  `documento` varchar(255) DEFAULT NULL,
  `telefono` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `saldo_cache_usd` decimal(20,2) NOT NULL DEFAULT 0.00,
  `saldo_cache_at` timestamp NULL DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `clientes_nombre_alias_fulltext` (`nombre`,`alias`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `comisiones_cuenta` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cuenta_id` bigint(20) unsigned DEFAULT NULL,
  `banco_id` bigint(20) unsigned DEFAULT NULL,
  `descripcion` varchar(100) NOT NULL,
  `tipo_calculo` enum('porcentaje','monto_fijo') NOT NULL,
  `valor` decimal(20,8) NOT NULL,
  `moneda_id` bigint(20) unsigned NOT NULL,
  `aplica_a` enum('ingreso','egreso','ambos') NOT NULL,
  `vigente_desde` date NOT NULL,
  `vigente_hasta` date DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comisiones_cuenta_moneda_id_foreign` (`moneda_id`),
  KEY `comisiones_cuenta_cuenta_id_activa_index` (`cuenta_id`,`activa`),
  KEY `comisiones_cuenta_banco_id_activa_index` (`banco_id`,`activa`),
  KEY `comisiones_cuenta_vigente_desde_vigente_hasta_index` (`vigente_desde`,`vigente_hasta`),
  CONSTRAINT `comisiones_cuenta_banco_id_foreign` FOREIGN KEY (`banco_id`) REFERENCES `bancos` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `comisiones_cuenta_cuenta_id_foreign` FOREIGN KEY (`cuenta_id`) REFERENCES `cuentas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `comisiones_cuenta_moneda_id_foreign` FOREIGN KEY (`moneda_id`) REFERENCES `monedas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `comisiones_metodo_pago` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre_metodo` varchar(80) NOT NULL,
  `cuenta_id` bigint(20) unsigned DEFAULT NULL,
  `descripcion` varchar(100) NOT NULL,
  `tipo_calculo` enum('porcentaje','monto_fijo') NOT NULL,
  `valor` decimal(20,8) NOT NULL,
  `moneda_id` bigint(20) unsigned NOT NULL,
  `vigente_desde` date NOT NULL,
  `vigente_hasta` date DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comisiones_metodo_pago_moneda_id_foreign` (`moneda_id`),
  KEY `comisiones_metodo_pago_nombre_metodo_activa_index` (`nombre_metodo`,`activa`),
  KEY `comisiones_metodo_pago_cuenta_id_activa_index` (`cuenta_id`,`activa`),
  CONSTRAINT `comisiones_metodo_pago_cuenta_id_foreign` FOREIGN KEY (`cuenta_id`) REFERENCES `cuentas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `comisiones_metodo_pago_moneda_id_foreign` FOREIGN KEY (`moneda_id`) REFERENCES `monedas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `comisiones_operacion` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `operacion_id` bigint(20) unsigned NOT NULL,
  `tipo` enum('cuenta','operador','metodo_pago','manual') NOT NULL,
  `origen_type` varchar(255) DEFAULT NULL,
  `origen_id` bigint(20) unsigned DEFAULT NULL,
  `descripcion` varchar(200) NOT NULL,
  `monto` decimal(20,2) NOT NULL,
  `moneda_id` bigint(20) unsigned NOT NULL,
  `monto_usd_equivalente` decimal(20,2) NOT NULL,
  `movimiento_id` bigint(20) unsigned DEFAULT NULL,
  `editada_por_id` bigint(20) unsigned DEFAULT NULL,
  `editada_at` timestamp NULL DEFAULT NULL,
  `razon_edicion` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_comision_origen` (`origen_type`,`origen_id`),
  KEY `comisiones_operacion_moneda_id_foreign` (`moneda_id`),
  KEY `comisiones_operacion_movimiento_id_foreign` (`movimiento_id`),
  KEY `comisiones_operacion_editada_por_id_foreign` (`editada_por_id`),
  KEY `comisiones_operacion_operacion_id_tipo_index` (`operacion_id`,`tipo`),
  CONSTRAINT `comisiones_operacion_editada_por_id_foreign` FOREIGN KEY (`editada_por_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `comisiones_operacion_moneda_id_foreign` FOREIGN KEY (`moneda_id`) REFERENCES `monedas` (`id`),
  CONSTRAINT `comisiones_operacion_movimiento_id_foreign` FOREIGN KEY (`movimiento_id`) REFERENCES `movimientos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `comisiones_operacion_operacion_id_foreign` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `comisiones_operador` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `titular_id` bigint(20) unsigned NOT NULL,
  `tipo_operacion_id` bigint(20) unsigned DEFAULT NULL,
  `descripcion` varchar(100) NOT NULL,
  `tipo_calculo` enum('porcentaje','monto_fijo') NOT NULL,
  `valor` decimal(20,8) NOT NULL,
  `moneda_id` bigint(20) unsigned NOT NULL,
  `base_calculo` enum('monto_operacion','ganancia_bruta') NOT NULL DEFAULT 'monto_operacion',
  `vigente_desde` date NOT NULL,
  `vigente_hasta` date DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comisiones_operador_tipo_operacion_id_foreign` (`tipo_operacion_id`),
  KEY `comisiones_operador_moneda_id_foreign` (`moneda_id`),
  KEY `comisiones_operador_titular_id_activa_index` (`titular_id`,`activa`),
  KEY `comisiones_operador_vigente_desde_vigente_hasta_index` (`vigente_desde`,`vigente_hasta`),
  CONSTRAINT `comisiones_operador_moneda_id_foreign` FOREIGN KEY (`moneda_id`) REFERENCES `monedas` (`id`),
  CONSTRAINT `comisiones_operador_tipo_operacion_id_foreign` FOREIGN KEY (`tipo_operacion_id`) REFERENCES `tipos_operacion` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `comisiones_operador_titular_id_foreign` FOREIGN KEY (`titular_id`) REFERENCES `titulares` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `cuentas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `titular_id` bigint(20) unsigned DEFAULT NULL,
  `cliente_id` bigint(20) unsigned DEFAULT NULL,
  `banco_id` bigint(20) unsigned DEFAULT NULL,
  `moneda_id` bigint(20) unsigned NOT NULL,
  `alias` varchar(255) NOT NULL,
  `tipo` enum('banco','plataforma','cash','wallet','zelle','efectivo','otro') NOT NULL,
  `numero_cuenta` varchar(255) DEFAULT NULL,
  `saldo_cache` decimal(20,2) NOT NULL DEFAULT 0.00,
  `saldo_cache_at` timestamp NULL DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `notas` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cuentas_titular_banco_alias_unique` (`titular_id`,`banco_id`,`alias`),
  UNIQUE KEY `cuentas_cliente_banco_alias_unique` (`cliente_id`,`banco_id`,`alias`),
  KEY `cuentas_banco_id_foreign` (`banco_id`),
  KEY `cuentas_moneda_id_foreign` (`moneda_id`),
  CONSTRAINT `cuentas_banco_id_foreign` FOREIGN KEY (`banco_id`) REFERENCES `bancos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cuentas_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `cuentas_moneda_id_foreign` FOREIGN KEY (`moneda_id`) REFERENCES `monedas` (`id`),
  CONSTRAINT `cuentas_titular_id_foreign` FOREIGN KEY (`titular_id`) REFERENCES `titulares` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `monedas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `simbolo` varchar(10) DEFAULT NULL,
  `es_fiat` tinyint(1) NOT NULL DEFAULT 1,
  `es_cripto` tinyint(1) NOT NULL DEFAULT 0,
  `decimales` tinyint(4) NOT NULL DEFAULT 2,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `monedas_codigo_unique` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `movimientos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `operacion_id` bigint(20) unsigned NOT NULL,
  `cuenta_id` bigint(20) unsigned NOT NULL,
  `moneda_id` bigint(20) unsigned NOT NULL,
  `monto` decimal(20,2) NOT NULL,
  `tasa_a_usd` decimal(20,8) NOT NULL,
  `monto_usd_equivalente` decimal(20,2) NOT NULL,
  `orden` smallint(5) unsigned NOT NULL DEFAULT 1,
  `estado` varchar(50) NOT NULL DEFAULT 'pendiente',
  `motivo_rechazo` text DEFAULT NULL,
  `validada_en` timestamp NULL DEFAULT NULL,
  `validada_por_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movimientos_moneda_id_foreign` (`moneda_id`),
  KEY `movimientos_cuenta_id_created_at_index` (`cuenta_id`,`created_at`),
  KEY `movimientos_operacion_id_orden_index` (`operacion_id`,`orden`),
  KEY `movimientos_cuenta_id_moneda_id_index` (`cuenta_id`,`moneda_id`),
  KEY `movimientos_validada_por_id_foreign` (`validada_por_id`),
  CONSTRAINT `movimientos_cuenta_id_foreign` FOREIGN KEY (`cuenta_id`) REFERENCES `cuentas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `movimientos_moneda_id_foreign` FOREIGN KEY (`moneda_id`) REFERENCES `monedas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `movimientos_operacion_id_foreign` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `movimientos_validada_por_id_foreign` FOREIGN KEY (`validada_por_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `transacciones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `operacion_id` bigint(20) unsigned NOT NULL,
  `cuenta_origen_id` bigint(20) unsigned NOT NULL,
  `cuenta_destino_id` bigint(20) unsigned NOT NULL,
  `moneda_id` bigint(20) unsigned NOT NULL,
  `monto` decimal(20,2) NOT NULL,
  `tasa_aplicada` decimal(20,8) DEFAULT NULL,
  `tasas_snapshot` json DEFAULT NULL,
  `metodo_pago` varchar(50) DEFAULT NULL,
  `comprobante` varchar(500) DEFAULT NULL,
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

CREATE TABLE `operaciones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `tipo_operacion_id` bigint(20) unsigned NOT NULL,
  `cliente_id` bigint(20) unsigned DEFAULT NULL,
  `cliente_emisor_id` bigint(20) unsigned DEFAULT NULL,
  `cliente_receptor_id` bigint(20) unsigned DEFAULT NULL,
  `categoria_gasto_id` bigint(20) unsigned DEFAULT NULL,
  `operador_id` bigint(20) unsigned NOT NULL,
  `tasa_aplicada` decimal(20,8) DEFAULT NULL,
  `tasa_compra` decimal(20,8) DEFAULT NULL,
  `tasa_venta` decimal(20,8) DEFAULT NULL,
  `genera_comision` tinyint(1) NOT NULL DEFAULT 0,
  `monto_comision` decimal(20,2) NOT NULL DEFAULT 0.00,
  `tipo_comision` varchar(50) DEFAULT NULL,
  `tasa_sugerida` decimal(20,8) DEFAULT NULL,
  `tasa_diaria_id` bigint(20) unsigned DEFAULT NULL,
  `sin_tasa_referencia` tinyint(1) NOT NULL DEFAULT 0,
  `tasa_mercado_snapshot` decimal(20,8) DEFAULT NULL,
  `fuente_tasa_mercado` varchar(30) DEFAULT NULL,
  `ganancia_bruta_usd` decimal(20,2) NOT NULL DEFAULT 0.00,
  `ganancia_real_usd` decimal(20,2) DEFAULT NULL,
  `ganancia_bruta_ves` decimal(20,2) NOT NULL DEFAULT 0.00,
  `ganancia_real_ves` decimal(20,2) DEFAULT NULL,
  `total_comisiones_usd` decimal(20,2) NOT NULL DEFAULT 0.00,
  `total_comisiones_ves` decimal(20,2) NOT NULL DEFAULT 0.00,
  `ganancia_neta_usd` decimal(20,2) NOT NULL DEFAULT 0.00,
  `ganancia_neta_ves` decimal(20,2) NOT NULL DEFAULT 0.00,
  `referencia` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `estatus` enum('verificado','en_revision','sin_verificar') NOT NULL DEFAULT 'sin_verificar',
  `estado_pool` enum('pendiente','asignada','pagada','cancelada') NOT NULL DEFAULT 'pendiente',
  `pagador_id` bigint(20) unsigned DEFAULT NULL,
  `asignada_at` timestamp NULL DEFAULT NULL,
  `pagada_at` timestamp NULL DEFAULT NULL,
  `cancelada_at` timestamp NULL DEFAULT NULL,
  `motivo_cancelacion` text DEFAULT NULL,
  `verificado_at` timestamp NULL DEFAULT NULL,
  `verificado_por_id` bigint(20) unsigned DEFAULT NULL,
  `origen` enum('manual','importado','ajuste_apertura') NOT NULL DEFAULT 'manual',
  `origen_referencia` varchar(100) DEFAULT NULL,
  `estado` varchar(50) NOT NULL DEFAULT 'en_espera' COMMENT 'solicitud, en_progreso, cerrada, cancelada',
  `tasas_snapshot` json DEFAULT NULL COMMENT 'Snapshot de tasas BCV/USDT al momento de la solicitud',
  `en_progreso_at` timestamp NULL DEFAULT NULL,
  `sla_notificado_en` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `operaciones_origen_referencia_unique` (`origen_referencia`),
  KEY `operaciones_tipo_operacion_id_foreign` (`tipo_operacion_id`),
  KEY `operaciones_categoria_gasto_id_foreign` (`categoria_gasto_id`),
  KEY `operaciones_verificado_por_id_foreign` (`verificado_por_id`),
  KEY `operaciones_fecha_tipo_operacion_id_index` (`fecha`,`tipo_operacion_id`),
  KEY `operaciones_estatus_index` (`estatus`),
  KEY `operaciones_estado_index` (`estado`),
  KEY `operaciones_cliente_id_index` (`cliente_id`),
  KEY `operaciones_operador_id_index` (`operador_id`),
  KEY `operaciones_tasa_diaria_id_foreign` (`tasa_diaria_id`),
  KEY `operaciones_pagador_id_foreign` (`pagador_id`),
  CONSTRAINT `operaciones_categoria_gasto_id_foreign` FOREIGN KEY (`categoria_gasto_id`) REFERENCES `categorias_gasto` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `operaciones_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `operaciones_operador_id_foreign` FOREIGN KEY (`operador_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `operaciones_pagador_id_foreign` FOREIGN KEY (`pagador_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `operaciones_tasa_diaria_id_foreign` FOREIGN KEY (`tasa_diaria_id`) REFERENCES `tasas_diarias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `operaciones_tipo_operacion_id_foreign` FOREIGN KEY (`tipo_operacion_id`) REFERENCES `tipos_operacion` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `operaciones_verificado_por_id_foreign` FOREIGN KEY (`verificado_por_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tasas_diarias` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `moneda_base_id` bigint(20) unsigned NOT NULL,
  `moneda_cotizada_id` bigint(20) unsigned NOT NULL,
  `tasa_compra` decimal(20,8) NOT NULL,
  `tasa_compra_minima` decimal(20,8) DEFAULT NULL,
  `tasa_venta` decimal(20,8) NOT NULL,
  `tasa_venta_minima` decimal(20,8) DEFAULT NULL,
  `definida_por_id` bigint(20) unsigned NOT NULL,
  `notas` text DEFAULT NULL,
  `vigente_desde` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `vigente_hasta` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasas_diarias_moneda_cotizada_id_foreign` (`moneda_cotizada_id`),
  KEY `tasas_diarias_definida_por_id_foreign` (`definida_por_id`),
  KEY `idx_tasa_dia_par` (`fecha`,`moneda_base_id`,`moneda_cotizada_id`),
  KEY `idx_tasa_vigencia` (`moneda_base_id`,`moneda_cotizada_id`,`vigente_desde`,`vigente_hasta`),
  CONSTRAINT `tasas_diarias_definida_por_id_foreign` FOREIGN KEY (`definida_por_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `tasas_diarias_moneda_base_id_foreign` FOREIGN KEY (`moneda_base_id`) REFERENCES `monedas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `tasas_diarias_moneda_cotizada_id_foreign` FOREIGN KEY (`moneda_cotizada_id`) REFERENCES `monedas` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tasas_mercado` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fuente` varchar(30) NOT NULL,
  `moneda_base_id` bigint(20) unsigned NOT NULL,
  `moneda_cotizada_id` bigint(20) unsigned NOT NULL,
  `valor` decimal(20,8) NOT NULL,
  `capturado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payload_original` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasas_mercado_moneda_cotizada_id_foreign` (`moneda_cotizada_id`),
  KEY `idx_tasas_fuente_capturado` (`fuente`,`capturado_en`),
  KEY `idx_tasas_par_capturado` (`moneda_base_id`,`moneda_cotizada_id`,`capturado_en`),
  KEY `tasas_mercado_capturado_en_index` (`capturado_en`),
  CONSTRAINT `tasas_mercado_moneda_base_id_foreign` FOREIGN KEY (`moneda_base_id`) REFERENCES `monedas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `tasas_mercado_moneda_cotizada_id_foreign` FOREIGN KEY (`moneda_cotizada_id`) REFERENCES `monedas` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tipos_operacion` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(30) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `afecta_cliente` tinyint(1) NOT NULL DEFAULT 0,
  `afecta_fifo` tinyint(1) NOT NULL DEFAULT 0,
  `genera_ganancia` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_operacion_codigo_unique` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `titulares` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `alias` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `titulares_nombre_unique` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `titular_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_titular_id_foreign` (`titular_id`),
  CONSTRAINT `users_titular_id_foreign` FOREIGN KEY (`titular_id`) REFERENCES `titulares` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

