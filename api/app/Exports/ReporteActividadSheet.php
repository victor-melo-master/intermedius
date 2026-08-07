<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Excel sheet with per-operator activity for a period.
 */
class ReporteActividadSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private readonly Collection $actividad,
    ) {}

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Actividad por operador';
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return ['Operador', 'Total Operaciones', 'Volumen USD'];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        return $this->actividad->map(fn ($o) => [
            $o['operador'] ?? '—',
            $o['total_operaciones'] ?? 0,
            number_format($o['volumen_usd'] ?? 0, 2),
        ]);
    }
}
