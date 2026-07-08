<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Multi-sheet Excel export for operator commissions.
 * First sheet is a Resumen summary; subsequent sheets are per-titular detail.
 */
class ComisionesOperadoresExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private readonly Collection $datos,
        private readonly Carbon $desde,
        private readonly Carbon $hasta,
    ) {}

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [new ComisionesResumenSheet($this->datos, $this->desde, $this->hasta)];

        foreach ($this->datos as $row) {
            $sheets[] = new ComisionesDetalleSheet($row['titular'], $row['detalle']);
        }

        return $sheets;
    }
}
