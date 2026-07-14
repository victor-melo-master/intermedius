# Seeding de desarrollo — Intermedius

## Seeders disponibles

| Seeder | Contenido | Ejecución |
|--------|-----------|-----------|
| `CatalogosBaseSeeder` | Monedas, roles, permisos, tipos de operación, categorías de gasto | `php artisan db:seed --class=CatalogosBaseSeeder` |
| `AdminUserSeeder` | Admin `admin@test.com` (pass aleatorio visible en consola) | Se ejecuta automáticamente con `db:seed` |
| `DesarrolloSedeer` | Bancos (10 VE + 3 US), titular/cliente Intermedius, ~10 cuentas con saldos, 6 tasas diarias, 2 clientes de prueba, usuario `intermedius@test.com` / `password123` (super_admin) | `php artisan db:seed --class=DesarrolloSedeer` |

## Ejecución completa (todo)

```bash
docker compose exec api php artisan db:seed
```

Esto ejecuta: `CatalogosBaseSeeder` → `AdminUserSeeder` → `DesarrolloSedeer`

## Verificación rápida

```bash
# Conteo de registros por tabla
docker compose exec api php artisan tinker --execute="echo json_encode([
  'bancos'        => \App\Models\Banco::count(),
  'titulares'     => \App\Models\Titular::count(),
  'clientes'      => \App\Models\Cliente::count(),
  'cuentas'       => \App\Models\Cuenta::count(),
  'tasas_hoy'     => \App\Models\TasaDiaria::whereDate('fecha', now()->toDateString())->count(),
  'users'         => \App\Models\User::count(),
], JSON_PRETTY_PRINT);"

# Listar cuentas de Intermedius
docker compose exec api php artisan tinker --execute="\App\Models\Cuenta::where('titular_id', 1)->get()->pluck('alias')"

# Probar login con usuario de desarrollo
# POST /api/login { "email": "intermedius@test.com", "password": "password123" }
```

## Cuentas creadas por DesarrolloSedeer

### Intermedius (titular_id=1, cliente_id=1)
| Alias | Banco | Moneda | Saldo |
|-------|-------|--------|-------|
| Intermedius - Bank of America (USD) | Bank of America | USD | \$1,000 |
| Intermedius - Chase (USD) | Chase | USD | \$1,000 |
| Intermedius - Wells Fargo (USD) | Wells Fargo | USD | \$1,000 |
| Intermedius - Efectivo USD | — | USD | \$2,000 |
| Intermedius - Efectivo EUR | — | EUR | \$1,000 |
| Intermedius - Efectivo COP | — | COP | \$1,000 |
| Intermedius - Efectivo USDT | — | USDT | \$1,000 |
| Intermedius - Banesco (VES) | Banesco | VES | Bs. 1,000,000 |
| Intermedius - Mercantil (VES) | Mercantil | VES | Bs. 1,000,000 |
| Intermedius - Provincial (VES) | Provincial | VES | Bs. 1,000,000 |

### Clientes de prueba
| Nombre | Documento | Cuenta VES |
|--------|-----------|------------|
| María Pérez | V-12345678 | 1 cuenta en banco VE aleatorio |
| Carlos Gómez | V-87654321 | 1 cuenta en banco VE aleatorio |

## Tasas diarias (vigentes hoy)

| Par | Compra | Venta |
|-----|--------|-------|
| USD/VES | 40.50 | 41.00 |
| EUR/VES | 44.00 | 44.50 |
| COP/VES | 0.010 | 0.011 |
| USDT/VES | 40.30 | 40.80 |
| USD/EUR | 0.92 | 0.93 |
| USD/COP | 3,800 | 3,850 |
