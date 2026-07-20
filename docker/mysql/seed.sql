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

-- Usuario administrador por defecto
-- Contraseña: admin (debe ser cambiada inmediatamente en producción)
INSERT IGNORE INTO users (name, email, password, activo, email_verified_at, created_at, updated_at) VALUES
('Admin Principal', 'admin@test.com', '$2y$12$MG35Y8Ei4AGqy3Glw4OMaOzRnqux1O5S0pw62Rs9IjjpMs2lVjLay', 1, NOW(), NOW(), NOW());

-- Asignación del rol super_admin al usuario administrador
INSERT IGNORE INTO model_has_roles (role_id, model_type, model_id)
SELECT r.id, 'App\\Models\\User', u.id
FROM roles r, users u
WHERE r.name = 'super_admin' AND u.email = 'admin@test.com';

-- Tipos de operación
INSERT INTO tipos_operacion (codigo, nombre, afecta_cliente, afecta_fifo, genera_ganancia, activo, created_at, updated_at) VALUES
('venta_usd',       'Venta de USD',           1, 1, 1, 1, NOW(), NOW()),
('compra_usd',      'Compra de USD',          1, 1, 0, 1, NOW(), NOW()),
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

SET FOREIGN_KEY_CHECKS=1;
