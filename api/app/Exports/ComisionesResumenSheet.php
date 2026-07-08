<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Excel sheet for the summary view of operator commissions.
 * Shows per-titular totals: number of operations and total commission in USD.
 */
class ComisionesResumenSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private readonly Collection $datos,
        private readonly Carbon $desde,
        private readonly Carbon $hasta,
    ) {}

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Resumen';
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return ['Titular', 'Total Operaciones', 'Total Comisiones USD', 'Período'];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        $periodo = $this->desde->format('d/m/Y') . ' - ' . $this->hasta->format('d/m/Y');

        return $this->datos->map(fn ($row) => [
            $row['titular'],
            $row['total_operaciones'],
            number_format($row['total_comisiones_usd'], 4),
            $periodo,
        ]);
    }
}
