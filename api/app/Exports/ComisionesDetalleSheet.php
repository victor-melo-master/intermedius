<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ComisionesDetalleSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private readonly string $titular,
        private readonly Collection $comisiones,
    ) {}

    public function title(): string
    {
        return mb_substr($this->titular, 0, 31);
    }

    public function headings(): array
    {
        return ['Operación ID', 'Fecha', 'Descripción', 'Monto', 'Moneda', 'Monto USD Equiv.'];
    }

    public function collection(): Collection
    {
        return $this->comisiones->map(fn ($c) => [
            $c->operacion_id,
            optional($c->operacion)->fecha?->format('d/m/Y'),
            $c->descripcion,
            number_format($c->monto, 4),
            optional($c->moneda)->codigo,
            number_format($c->monto_usd_equivalente, 4),
        ]);
    }
}
