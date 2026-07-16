<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Excel sheet for the detail view of a single titular's commissions.
 * Shows each commission line with operation ID, date, description, amount, currency, and USD equivalent.
 */
class ComisionesDetalleSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private readonly string $titular,
        private readonly Collection $comisiones,
    ) {}

    /**
     * @return string
     */
    public function title(): string
    {
        return mb_substr($this->titular, 0, 31);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return ['Operación ID', 'Fecha', 'Descripción', 'Monto', 'Moneda', 'Monto USD Equiv.'];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        return $this->comisiones->map(fn ($c) => [
            $c->operacion_id,
            optional($c->operacion)->fecha?->format('d/m/Y'),
            $c->descripcion,
            number_format($c->monto, 2),
            optional($c->moneda)->codigo,
            number_format($c->monto_usd_equivalente, 2),
        ]);
    }
}
