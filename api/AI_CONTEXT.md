# Contexto de desarrollo — Intermedius

## Seeders disponibles

| Seeder | Contenido | Ejecución |
|--------|-----------|-----------|
| `CatalogosBaseSeeder` | Monedas, roles, permisos, tipos de operación, categorías de gasto | `php artisan db:seed --class=CatalogosBaseSeeder` |
| `AdminUserSeeder` | Admin `admin@test.com` (pass aleatorio visible en consola) | Se ejecuta automáticamente con `db:seed` |
| `DesarrolloSedeer` | Bancos (10 VE + 3 US), titular/cliente Intermedius, ~10 cuentas con saldos, 6 tasas diarias, 2 clientes de prueba, usuario `intermedius@test.com` / `password123` (super_admin) | `php artisan db:seed --class=DesarrolloSedeer` |

## Ejecución completa

```bash
docker compose exec api php artisan db:seed
```

## Frontend — Estructura de transacciones

### Payload que envía `submit()` al backend (`POST /api/v1/operaciones`)

```json
{
  "fecha": "YYYY-MM-DD",
  "tipo_codigo": "venta_usd|compra_usd",
  "cliente_id": 5,
  "operador_id": 1,
  "tasa_aplicada": 40.50,
  "descripcion": "...",
  "transacciones": [
    {
      "cuenta_origen_id": 10,
      "cuenta_destino_id": 20,
      "moneda_id": 2,
      "monto": 100.00
    }
  ],
  "genera_comision": false,
  "monto_comision": 0
}
```

### Componentes clave

| Archivo | Propósito |
|---------|-----------|
| `frontend/src/views/OperacionFormView.vue` | Formulario de operaciones con transacciones dinámicas |
| `frontend/src/components/operaciones/TransaccionRow.vue` | Fila individual (moneda, salida, entrada, monto) |
| `frontend/src/components/CalculadoraBidireccional.vue` | Calculadora monto/tasa/bolívares con 2 modos |

### Reglas de negocio

- Las transacciones se envían como `transacciones[]` en el body (montos positivos, sin signo)
- Las comisiones son a nivel de operación (no por transacción)
- El backend rechaza edición si estado !== 'en_espera'
- Después de crear, se redirige a `/pool`
