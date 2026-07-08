<?php

namespace App\Services\Reportes;

use App\Models\ComisionOperacion;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteComisionesOperadoresService
{
    /**
     * Genera el reporte en memoria (sin persistir archivos).
     * Retorna una Collection con un row por titular.
     *
     * @return Collection<array{titular_id, titular, total_operaciones, total_comisiones_usd, detalle}>
     */
    public function generar($desde, $hasta): Collection
    {
        $comisiones = ComisionOperacion::whereHas('operacion', function ($q) use ($desde, $hasta) {
            $q->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()]);
        })
        ->where('tipo', 'operador')
        ->with(['operacion', 'moneda', 'origen'])
        ->get();

        return $comisiones
            ->groupBy(fn ($c) => optional($c->origen)->titular_id)
            ->filter(fn ($grupo, $titularId) => $titularId !== null)
            ->map(function (Collection $grupo) {
                $titular = optional($grupo->first()->origen)->titular;
                return [
                    'titular_id'            => $titular?->id,
                    'titular'               => $titular?->nombre ?? 'Desconocido',
                    'total_operaciones'     => $grupo->pluck('operacion_id')->unique()->count(),
                    'total_comisiones_usd'  => round($grupo->sum('monto_usd_equivalente'), 4),
                    'detalle'               => $grupo->values(),
                ];
            })
            ->values();
    }

    /**
     * Exporta el reporte a Excel.
     * Retorna el path relativo en storage (usable con Storage::url()).
     */
    public function exportarExcel($desde, $hasta): string
    {
        $datos  = $this->generar($desde, $hasta);
        $mes    = $desde->format('Y-m');
        $dir    = config('reportes.comisiones_operadores.storage_path', 'reportes/comisiones');
        $nombre = "comisiones_operadores_{$mes}.xlsx";
        $path   = "{$dir}/{$nombre}";

        Excel::store(
            new \App\Exports\ComisionesOperadoresExport($datos, $desde, $hasta),
            $path,
            'local'
        );

        return $path;
    }

    /**
     * Exporta el reporte a PDF.
     * Retorna el path relativo en storage (usable con Storage::url()).
     */
    public function exportarPdf($desde, $hasta): string
    {
        $datos  = $this->generar($desde, $hasta);
        $mes    = $desde->format('Y-m');
        $dir    = config('reportes.comisiones_operadores.storage_path', 'reportes/comisiones');
        $nombre = "comisiones_operadores_{$mes}.pdf";
        $path   = "{$dir}/{$nombre}";

        $pdf = Pdf::loadView('reportes.comisiones_operadores', [
            'datos' => $datos,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);

        Storage::put($path, $pdf->output());

        return $path;
    }
}
