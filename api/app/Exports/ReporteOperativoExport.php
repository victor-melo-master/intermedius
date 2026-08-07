<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Multi-sheet Excel export for the operational summary report.
 * Sheets: Resumen (KPIs), Volúmenes por moneda, Actividad por operador.
 */
class ReporteOperativoExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private readonly array $resumen,
    ) {}

    /**
     * @return array
     */
    public function sheets(): array
    {
        return [
            new ReporteResumenSheet($this->resumen),
            new ReporteVolumenesSheet(collect($this->resumen['volumenes'] ?? [])),
            new ReporteActividadSheet(collect($this->resumen['por_operador'] ?? [])),
        ];
    }
}
