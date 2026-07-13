-- Datos iniciales (inserciones seguras)
-- Se ejecuta después de artisan migrate

SET FOREIGN_KEY_CHECKS=0;

INSERT IGNORE INTO roles (name, guard_name, created_at, updated_at) VALUES
('super_admin', 'web', NOW(), NOW()),
('admin', 'web', NOW(), NOW()),
('operador', 'web', NOW(), NOW()),
('pagador', 'web', NOW(), NOW()),
('contador', 'web', NOW(), NOW()),
('lectura', 'web', NOW(), NOW());

INSERT IGNORE INTO users (name, email, password, activo, email_verified_at, created_at, updated_at) VALUES
('Admin Principal', 'admin@test.com', '$2y$12$MG35Y8Ei4AGqy3Glw4OMaOzRnqux1O5S0pw62Rs9IjjpMs2lVjLay', 1, NOW(), NOW(), NOW());

INSERT IGNORE INTO model_has_roles (role_id, model_type, model_id)
SELECT r.id, 'App\\Models\\User', u.id
FROM roles r, users u
WHERE r.name = 'super_admin' AND u.email = 'admin@test.com';

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

INSERT INTO monedas (codigo, nombre, simbolo, es_fiat, es_cripto, decimales, activa, created_at, updated_at) VALUES
('VES',  'Bolívar Venezolano',  'Bs.', 1, 0, 2, 1, NOW(), NOW()),
('USD',  'Dólar Estadounidense', '$', 1, 0, 2, 1, NOW(), NOW()),
('USDT', 'Tether USD',         '₮',   0, 1, 6, 1, NOW(), NOW()),
('EUR',  'Euro',               '€',   1, 0, 2, 1, NOW(), NOW()),
('COP',  'Peso Colombiano',    '$',   1, 0, 2, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

SET FOREIGN_KEY_CHECKS=1;
