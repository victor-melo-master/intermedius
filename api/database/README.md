# database/ — Schema, Migrations & Seeders

```
database/
├── factories/       # Model factories (testing)
├── migrations/      # (vacío — migraciones reemplazadas por schema dump)
├── schema/          # Dump SQL del esquema completo
├── seeders/         # Seeders para datos iniciales
└── init.sql         # Script de inicialización
```

## Schema

`schema/mysql.sql` contiene el dump completo del esquema. Reemplaza a las 32 migraciones anteriores. Al crear la DB desde cero, se carga este SQL directamente y luego se ejecutan los seeders.

## Seeders

| Seeder | Descripción |
|---|---|
| `DatabaseSeeder` | Orquesta todos los seeders |
| `AdminUserSeeder` | Crea el usuario admin inicial |
| `CatalogosBaseSeeder` | Población inicial de bancos, monedas, tipos de operación, etc. |

## Convención

No se usan migraciones tradicionales de Laravel. El esquema se versiona mediante el dump SQL en `schema/`. Los cambios estructurales se reflejan actualizando el dump.
