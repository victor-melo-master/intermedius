-- Datos iniciales (inserciones seguras)
-- Se ejecuta después de artisan migrate

SET FOREIGN_KEY_CHECKS=0;

-- Roles del sistema
INSERT IGNORE INTO roles (name, guard_name, created_at, updated_at) VALUES
('super_admin', 'web', NOW(), NOW()),
('admin', 'web', NOW(), NOW()),
('operador', 'web', NOW(), NOW()),
('pagador', 'web', NOW(), NOW()),
('contador', 'web', NOW(), NOW()),
('lectura', 'web', NOW(), NOW());

-- Permisos del pool
INSERT IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES
('pool.tomar', 'web', NOW(), NOW()),
('pool.pagar', 'web', NOW(), NOW()),
('pool.cancelar', 'web', NOW(), NOW());

-- Asignar permisos a roles
INSERT IGNORE INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p, roles r
WHERE p.name = 'pool.tomar' AND r.name IN ('pagador', 'admin', 'super_admin');

INSERT IGNORE INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p, roles r
WHERE p.name = 'pool.pagar' AND r.name IN ('pagador', 'admin', 'super_admin');

INSERT IGNORE INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p, roles r
WHERE p.name = 'pool.cancelar' AND r.name IN ('admin', 'super_admin');

-- Usuarios por defecto (un usuario por rol)
-- Contraseña de todos: password123 (debe ser cambiada inmediatamente en producción)
INSERT IGNORE INTO users (name, email, password, activo, email_verified_at, created_at, updated_at) VALUES
('Admin Principal', 'admin@test.com', '$2y$12$09G34wGwUcaBo3x5QxFyxOG3wJmEy5Oz0jvBF4pooj2I.8QegH41u', 1, NOW(), NOW(), NOW()),
('Administrador',   'gerente@test.com', '$2y$12$09G34wGwUcaBo3x5QxFyxOG3wJmEy5Oz0jvBF4pooj2I.8QegH41u', 1, NOW(), NOW(), NOW()),
('Operador',        'operador@test.com', '$2y$12$09G34wGwUcaBo3x5QxFyxOG3wJmEy5Oz0jvBF4pooj2I.8QegH41u', 1, NOW(), NOW(), NOW()),
('Pagador',         'pagador@test.com', '$2y$12$09G34wGwUcaBo3x5QxFyxOG3wJmEy5Oz0jvBF4pooj2I.8QegH41u', 1, NOW(), NOW(), NOW()),
('Contador',        'contador@test.com', '$2y$12$09G34wGwUcaBo3x5QxFyxOG3wJmEy5Oz0jvBF4pooj2I.8QegH41u', 1, NOW(), NOW(), NOW()),
('Lectura',         'lectura@test.com', '$2y$12$09G34wGwUcaBo3x5QxFyxOG3wJmEy5Oz0jvBF4pooj2I.8QegH41u', 1, NOW(), NOW(), NOW());

-- Actualizar clave de usuarios existentes (INSERT IGNORE no modifica filas ya creadas)
UPDATE users SET password = '$2y$12$09G34wGwUcaBo3x5QxFyxOG3wJmEy5Oz0jvBF4pooj2I.8QegH41u'
WHERE email IN ('admin@test.com', 'gerente@test.com', 'operador@test.com', 'pagador@test.com', 'contador@test.com', 'lectura@test.com');

-- Asignación de roles a cada usuario
INSERT IGNORE INTO model_has_roles (role_id, model_type, model_id)
SELECT r.id, 'App\\Models\\User', u.id
FROM roles r, users u
WHERE r.name = 'super_admin' AND u.email = 'admin@test.com';

INSERT IGNORE INTO model_has_roles (role_id, model_type, model_id)
SELECT r.id, 'App\\Models\\User', u.id
FROM roles r, users u
WHERE r.name = 'admin' AND u.email = 'gerente@test.com';

INSERT IGNORE INTO model_has_roles (role_id, model_type, model_id)
SELECT r.id, 'App\\Models\\User', u.id
FROM roles r, users u
WHERE r.name = 'operador' AND u.email = 'operador@test.com';

INSERT IGNORE INTO model_has_roles (role_id, model_type, model_id)
SELECT r.id, 'App\\Models\\User', u.id
FROM roles r, users u
WHERE r.name = 'pagador' AND u.email = 'pagador@test.com';

INSERT IGNORE INTO model_has_roles (role_id, model_type, model_id)
SELECT r.id, 'App\\Models\\User', u.id
FROM roles r, users u
WHERE r.name = 'contador' AND u.email = 'contador@test.com';

INSERT IGNORE INTO model_has_roles (role_id, model_type, model_id)
SELECT r.id, 'App\\Models\\User', u.id
FROM roles r, users u
WHERE r.name = 'lectura' AND u.email = 'lectura@test.com';

-- Tipos de operación
INSERT INTO tipos_operacion (codigo, nombre, afecta_cliente, afecta_fifo, genera_ganancia, activo, created_at, updated_at) VALUES
('venta_usd',       'Venta de USD',           1, 1, 0, 1, NOW(), NOW()),
('compra_usd',      'Compra de USD',          1, 1, 1, 1, NOW(), NOW()),
('cambio',          'Cambio de moneda',       0, 0, 0, 1, NOW(), NOW()),
('intermediada',    'Operación Intermediada', 1, 1, 1, 1, NOW(), NOW()),
('gasto',           'Gasto operativo',        0, 0, 0, 1, NOW(), NOW()),
('comision',        'Comisión',               1, 0, 1, 1, NOW(), NOW()),
('traslado',        'Traslado interno',       0, 0, 0, 1, NOW(), NOW()),
('ajuste',          'Ajuste contable',        0, 0, 0, 1, NOW(), NOW()),
('ajuste_apertura', 'Ajuste de apertura',     0, 1, 0, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- Monedas predefinidas
INSERT INTO monedas (codigo, nombre, simbolo, es_fiat, es_cripto, decimales, activa, created_at, updated_at) VALUES
('VES',  'Bolívar Venezolano',  'Bs.', 1, 0, 2, 1, NOW(), NOW()),
('USD',  'Dólar Estadounidense', '$', 1, 0, 2, 1, NOW(), NOW()),
('USDT', 'Tether USD',         '₮',   0, 1, 6, 1, NOW(), NOW()),
('EUR',  'Euro',               '€',   1, 0, 2, 1, NOW(), NOW()),
('COP',  'Peso Colombiano',    '$',   1, 0, 2, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- Ajustes generales de la aplicación
INSERT IGNORE INTO ajustes (clave, valor, descripcion, created_at, updated_at) VALUES
('password_segura', '1', 'Rechaza contraseñas comprometidas en filtraciones públicas (HIBP).', NOW(), NOW()),
('envio_emails', '1', 'Habilita o deshabilita el envío de correos electrónicos desde la aplicación.', NOW(), NOW());

-- Bancos de Venezuela
INSERT IGNORE INTO bancos (nombre, codigo, pais, activo, created_at, updated_at) VALUES
('Banesco',           '0134', 'VE', 1, NOW(), NOW()),
('Mercantil',         '0105', 'VE', 1, NOW(), NOW()),
('Banco de Venezuela','0102', 'VE', 1, NOW(), NOW()),
('Provincial',        '0108', 'VE', 1, NOW(), NOW()),
('Bancamiga',         '0172', 'VE', 1, NOW(), NOW()),
('Banco del Tesoro',  '0163', 'VE', 1, NOW(), NOW()),
('Bancaribe',         '0114', 'VE', 1, NOW(), NOW()),
('Banco Nacional de Crédito', '0191', 'VE', 1, NOW(), NOW()),
('Banco Plaza',       '0138', 'VE', 1, NOW(), NOW()),
('Banco Exterior',    '0115', 'VE', 1, NOW(), NOW());

-- Bancos internacionales
INSERT IGNORE INTO bancos (nombre, codigo, pais, activo, created_at, updated_at) VALUES
('Banesco Panamá',    NULL, 'PA', 1, NOW(), NOW()),
('Mercantil Panamá',  NULL, 'PA', 1, NOW(), NOW()),
('Bancolombia',       NULL, 'CO', 1, NOW(), NOW()),
('Banco 53',          NULL, 'PA', 1, NOW(), NOW()),
('Bank of America',   NULL, 'US', 1, NOW(), NOW()),
('Chase',             NULL, 'US', 1, NOW(), NOW()),
('Wells Fargo',       NULL, 'US', 1, NOW(), NOW()),
('Truist Bank',       NULL, 'US', 1, NOW(), NOW());

-- Categorías de gasto
INSERT IGNORE INTO categorias_gasto (nombre, activa, created_at, updated_at) VALUES
('Servicios',       1, NOW(), NOW()),
('Alquiler',        1, NOW(), NOW()),
('Mantenimiento',   1, NOW(), NOW()),
('Personal',        1, NOW(), NOW()),
('Impuestos',       1, NOW(), NOW()),
('Comunicaciones',  1, NOW(), NOW()),
('Varios',          1, NOW(), NOW());

-- Historial de sesiones de usuario (login/logout)
CREATE TABLE IF NOT EXISTS `sesiones_usuario` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `token_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `login_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `logout_at` timestamp NULL DEFAULT NULL,
  `logout_tipo` enum('manual','expirada') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sesiones_usuario_user_id_login_at_index` (`user_id`,`login_at`),
  KEY `sesiones_usuario_token_id_index` (`token_id`),
  KEY `sesiones_usuario_user_id_foreign` (`user_id`),
  KEY `sesiones_usuario_token_id_foreign` (`token_id`),
  CONSTRAINT `sesiones_usuario_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sesiones_usuario_token_id_foreign` FOREIGN KEY (`token_id`) REFERENCES `personal_access_tokens` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS=1;
