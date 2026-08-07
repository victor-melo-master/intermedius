<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Excel sheet with the operational summary KPIs for a period.
 */
class ReporteResumenSheet implements FromCollection, WithTitle
{
    public function __construct(
        private readonly array $resumen,
    ) {}

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Resumen';
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        $r = $this->resumen;
        $periodo = $r['periodo']['desde'] . ' al ' . $r['periodo']['hasta'];

        $filas = [
            ['Período', $periodo],
            ['Total operaciones', $r['operaciones']['total'] ?? 0],
            ['Compras', $r['operaciones']['compras'] ?? 0],
            ['Ventas', $r['operaciones']['ventas'] ?? 0],
            ['Intermediadas', $r['operaciones']['intermediadas'] ?? 0],
            ['Ganancia bruta (USD)', number_format($r['ganancias']['bruta_usd'] ?? 0, 2)],
            ['Ganancia neta (USD)', number_format($r['ganancias']['neta_usd'] ?? 0, 2)],
            ['Efectivo pendiente (ops)', $r['efectivo_pendiente']['count'] ?? 0],
            ['Efectivo pendiente (USD)', number_format($r['efectivo_pendiente']['monto_usd'] ?? 0, 2)],
        ];

        return collect($filas);
    }
}
