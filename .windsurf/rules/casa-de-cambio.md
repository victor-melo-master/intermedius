---
description: Reglas del proyecto Casa de Cambio
alwaysApply: true
---

# Reglas del proyecto Casa de Cambio

## Stack
- Backend: Laravel 11 + PHP 8.3 + MySQL 8
- Frontend: Flutter 3.x (web responsive + Android/iOS)
- Auth: Laravel Sanctum
- Roles: spatie/laravel-permission
- Auditoría: spatie/laravel-activitylog
- Queues: database driver para iniciar, migrar a Redis después
- Estado Flutter: Riverpod
- Routing Flutter: go_router
- HTTP Flutter: dio

## Reglas de código no negociables
1. Todos los modelos Eloquent usan `protected $fillable` explícito, nunca `$guarded = []`.
2. Toda operación que modifica el ledger (operaciones/movimientos) DEBE ir dentro de DB::transaction().
3. Las migraciones siempre incluyen `down()` funcional.
4. Decimales monetarios: decimal(20, 4) para montos, decimal(20, 8) para tasas.
5. Nunca uses float para dinero. Si trabajas con cálculos, usa bcmath o castea a string en validación.
6. Comentarios y nombres de variables en español (es proyecto en español), pero código (clases, métodos) en inglés/español según convención Laravel.
7. Todo endpoint del API debe tener Form Request para validación.
8. Toda escritura al ledger valida la invariante de partida doble: suma de movimientos en USD = 0 (tolerancia 0.01).
9. Soft deletes en tablas con datos sensibles: cuentas, clientes, operaciones.
10. Nunca elimines registros del ledger; usa el tipo de operación "ajuste" para corregir.

## Modelo de dominio
- Una operación tiene N movimientos. La operación es el "asiento contable", los movimientos las "partidas".
- Cuentas pertenecen a titulares (personas físicas que operan dinero), no a usuarios del sistema.
- Un usuario puede estar vinculado a un titular (operador) o no (admin, contador).
- Cada movimiento captura tasa_a_usd al momento; nunca se recalcula con tasa actual.
- FIFO se calcula en jobs asíncronos, no en línea.

## Roles
super_admin, admin, operador, contador, lectura. Permisos granulares vía spatie.

## Testing
- Cada servicio crítico (RegistroOperacionService, FifoService, ComisionesService) debe tener test unitario.
- Tests usan RefreshDatabase + factory para cada entidad.
- Coverage mínimo en servicios: 80%.

## Convenciones de naming
- Tablas: plural en español (operaciones, movimientos, cuentas).
- Modelos: singular en español (Operacion, Movimiento, Cuenta).
- Controllers: PascalCase + Controller (OperacionController).
- Services: PascalCase + Service en app/Services/{Dominio}/.
- Form Requests: app/Http/Requests/{Recurso}/StoreXRequest.php.
