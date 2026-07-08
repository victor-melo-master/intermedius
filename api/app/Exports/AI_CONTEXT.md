# Exports — AI Context

Exportaciones Excel de comisiones usando `maatwebsite/laravel-excel` (Laravel Excel 3.1).

## `ComisionesOperadoresExport`
- **Implementa**: `WithMultipleSheets`
- **Constructor**: `Collection $datos`, `Carbon $desde`, `Carbon $hasta`
- **sheets()**: Crea sheet de resumen + un sheet de detalle por cada titular

### Flujo
1. Sheet 1: `ComisionesResumenSheet` — resumen global del período
2. Sheets N: `ComisionesDetalleSheet` — uno por cada titular con su detalle individual
3. El nombre del archivo se define en el controller (`ReporteComisionesController@exportar`)

---

## `ComisionesResumenSheet`
- **Implementa**: `FromCollection`, `WithHeadings`, `WithTitle`
- **Title**: "Resumen"
- **Headings**: `['Titular', 'Total Operaciones', 'Total Comisiones USD', 'Período']`
- **Collection**: Mapea `datos` a filas con titular, total operaciones, total comisiones USD (formateado a 4 decimales), y período

---

## `ComisionesDetalleSheet`
- **Implementa**: `FromCollection`, `WithHeadings`, `WithTitle`
- **Title**: Nombre del titular (truncado a 31 chars por límite de Excel)
- **Headings**: `['Operación ID', 'Fecha', 'Descripción', 'Monto', 'Moneda', 'Monto USD Equiv.']`
- **Collection**: Mapea comisiones del titular a filas con operación_id, fecha, descripción, monto (4 decimales), código de moneda, monto USD equivalente (4 decimales)
