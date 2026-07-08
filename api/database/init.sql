-- ============================================================================
-- INTERMEDIUS — Script de inicialización de base de datos
-- Versión: 2026-07-08
-- ============================================================================

-- ──────────────────────────────────────────────────────────────────────────
-- Tipos de operación
-- ──────────────────────────────────────────────────────────────────────────
INSERT INTO `tipos_operacion` (`codigo`, `nombre`, `afecta_cliente`, `afecta_fifo`, `genera_ganancia`, `activo`, `created_at`, `updated_at`) VALUES
('venta_usd',       'Venta de USD',           1, 1, 1, 1, NOW(), NOW()),
('compra_usd',      'Compra de USD',          1, 1, 0, 1, NOW(), NOW()),
('cambio',          'Cambio de moneda',       0, 0, 0, 1, NOW(), NOW()),
('intermediada',    'Operación Intermediada', 1, 1, 1, 1, NOW(), NOW()),
('gasto',           'Gasto operativo',        0, 0, 0, 1, NOW(), NOW()),
('comision',        'Comisión',               1, 0, 1, 1, NOW(), NOW()),
('traslado',        'Traslado interno',       0, 0, 0, 1, NOW(), NOW()),
('ajuste',          'Ajuste contable',        0, 0, 0, 1, NOW(), NOW()),
('ajuste_apertura', 'Ajuste de apertura',     0, 1, 0, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- ──────────────────────────────────────────────────────────────────────────
-- Monedas
-- ──────────────────────────────────────────────────────────────────────────
INSERT INTO `monedas` (`codigo`, `nombre`, `simbolo`, `es_fiat`, `es_cripto`, `decimales`, `activa`, `created_at`, `updated_at`) VALUES
('VES',  'Bolívar Venezolano',   'Bs.', 1, 0, 2, 1, NOW(), NOW()),
('USD',  'Dólar Estadounidense', '$',   1, 0, 2, 1, NOW(), NOW()),
('USDT', 'Tether USD',           '₮',   0, 1, 6, 1, NOW(), NOW()),
('EUR',  'Euro',                 '€',   1, 0, 2, 1, NOW(), NOW()),
('COP',  'Peso Colombiano',      '$',   1, 0, 2, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- ──────────────────────────────────────────────────────────────────────────
-- Roles (Spatie)
-- ──────────────────────────────────────────────────────────────────────────
INSERT INTO `roles` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('super_admin', 'web', NOW(), NOW()),
('admin',       'web', NOW(), NOW()),
('operador',    'web', NOW(), NOW()),
('pagador',     'web', NOW(), NOW()),
('contador',    'web', NOW(), NOW()),
('lectura',     'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ──────────────────────────────────────────────────────────────────────────
-- Usuario admin por defecto
-- Email: admin@test.com / Password: password123
-- ──────────────────────────────────────────────────────────────────────────
INSERT INTO `users` (`name`, `email`, `password`, `activo`, `created_at`, `updated_at`) VALUES
('Admin Principal', 'admin@test.com', '$2y$12$t0Z0XJNvYpDqKm0Fs0MAJeNI0BmUJIW0fKoW0zWqL0fL4OV6QpT5q', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- Asignar rol super_admin al usuario admin
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`)
SELECT r.id, 'App\\Models\\User', u.id
FROM `roles` r, `users` u
WHERE r.name = 'super_admin' AND u.email = 'admin@test.com'
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`);
