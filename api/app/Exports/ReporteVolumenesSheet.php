<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Excel sheet with the traded volume per currency.
 */
class ReporteVolumenesSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private readonly Collection $volumenes,
    ) {}

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Volúmenes por moneda';
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return ['Moneda', 'Comprado', 'Vendido'];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        return $this->volumenes->map(fn ($v) => [
            $v['moneda'] ?? '—',
            number_format($v['comprado'] ?? 0, 2),
            number_format($v['vendido'] ?? 0, 2),
        ]);
    }
}
