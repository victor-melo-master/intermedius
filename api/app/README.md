# app/ — Application Logic

## Structure

```
app/
├── Exports/       # Excel/PDF exports (comisiones)
├── Http/          # Controllers, Requests, Resources
├── Jobs/          # Background jobs (queue via Laravel Horizon)
├── Models/        # Eloquent ORM models
├── Policies/      # Authorization policies (Spatie roles)
├── Providers/     # Service providers
└── Services/      # Business logic services
```

## Key Directories

| Directory | Purpose |
|-----------|---------|
| `Exports/` | Generación de reportes Excel de comisiones |
| `Http/Controllers/` | API endpoints organizados por recurso |
| `Http/Requests/` | Validación por recurso (Store/Update por entidad) |
| `Http/Resources/` | Transformación de respuestas JSON |
| `Jobs/` | Procesamiento asíncrono (tasas, comisiones, FIFO, auto-archivo) |
| `Models/` | 16 modelos Eloquent con SoftDeletes, relaciones y scopes |
| `Policies/` | 7 policies, una por entidad (CRUD + acciones especiales) |
| `Services/` | 5 módulos de lógica de negocio |
