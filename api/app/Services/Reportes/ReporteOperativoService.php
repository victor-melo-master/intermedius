<?php

namespace App\Services\Reportes;

use App\Models\Operacion;
use Carbon\Carbon;

/**
 * Service for generating the operational summary report (Resumen del período).
 * Provides aggregation logic shared between the dashboard preview and the
 * downloadable PDF/Excel exports.
 */
class ReporteOperativoService
{
    /**
     * Agrega las operaciones del período en un arreglo listo para JSON/PDF/Excel.
     *
     * @param string $desde      Fecha inicial (Y-m-d)
     * @param string $hasta      Fecha final (Y-m-d)
     * @param string|null $moneda  Código de moneda a filtrar (opcional)
     * @param int|null $operadorId ID de usuario operador (opcional)
     *
     * @return array{periodo: array, operaciones: array, volumenes: \Illuminate\Support\Collection, ganancias: array, por_operador: \Illuminate\Support\Collection, efectivo_pendiente: array}
     */
    public function resumen(string $desde, string $hasta, ?string $moneda = null, ?int $operadorId = null): array
    {
        $ops = Operacion::query()
            ->with([
                'tipoOperacion:id,codigo',
                'operador:id,name',
                'movimientos.moneda:id,codigo',
            ])
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->when($operadorId, fn ($q) => $q->where('operador_id', $operadorId))
            ->when($moneda, fn ($q) => $q->whereHas('movimientos.moneda', fn ($mq) => $mq->where('codigo', $moneda)))
            ->get();

        $compras = $ventas = $intermediadas = 0;
        $brutaUsd = $netaUsd = 0.0;
        $volPorMoneda = [];   // codigo => ['comprado' => x, 'vendido' => y]
        $porOperador  = [];   // operador_id => [...]
        $efCount = 0;
        $efMonto = 0.0;

        foreach ($ops as $op) {
            $codigo = $op->tipoOperacion?->codigo;

            match ($codigo) {
                'compra_usd' => $compras++,
                'venta_usd'  => $ventas++,
                'cambio'     => $intermediadas++,
                default      => null,
            };

            $brutaUsd += (float) $op->ganancia_bruta_usd;
            $netaUsd  += (float) $op->ganancia_neta_usd;

            // Volúmenes por moneda (excluye la local VES)
            foreach ($op->movimientos as $mov) {
                $codMoneda = $mov->moneda?->codigo;
                if (! $codMoneda || $codMoneda === 'VES') {
                    continue;
                }
                if ($moneda && $codMoneda !== $moneda) {
                    continue;
                }

                $volPorMoneda[$codMoneda] ??= ['comprado' => 0.0, 'vendido' => 0.0];
                $monto = (float) $mov->monto;

                if ($codigo === 'compra_usd' && $monto > 0) {
                    $volPorMoneda[$codMoneda]['comprado'] += $monto;
                } elseif ($codigo === 'venta_usd' && $monto < 0) {
                    $volPorMoneda[$codMoneda]['vendido'] += abs($monto);
                }
            }

            $volumenUsd = $this->volumenUsdDeOperacion($op);

            // Agregado por operador
            $oid = $op->operador_id;
            $porOperador[$oid] ??= [
                'operador'          => $op->operador?->name ?? '—',
                'total_operaciones' => 0,
                'volumen_usd'       => 0.0,
            ];
            $porOperador[$oid]['total_operaciones']++;
            $porOperador[$oid]['volumen_usd'] += $volumenUsd;

            // Efectivo pendiente (heurística sobre descripcion, Fase 1 sin columna dedicada)
            if (stripos((string) $op->descripcion, 'pendiente') !== false) {
                $efCount++;
                $efMonto += $volumenUsd;
            }
        }

        return [
            'periodo' => [
                'desde' => $desde,
                'hasta' => $hasta,
            ],
            'operaciones' => [
                'total'         => $ops->count(),
                'compras'       => $compras,
                'ventas'        => $ventas,
                'intermediadas' => $intermediadas,
            ],
            'volumenes' => collect($volPorMoneda)
                ->map(fn ($v, $cod) => [
                    'moneda'   => $cod,
                    'comprado' => round($v['comprado'], 2),
                    'vendido'  => round($v['vendido'], 2),
                ])
                ->values(),
            'ganancias' => [
                'bruta_usd' => round($brutaUsd, 2),
                'neta_usd'  => round($netaUsd, 2),
            ],
            'por_operador' => collect($porOperador)
                ->map(fn ($v) => [
                    'operador'          => $v['operador'],
                    'total_operaciones' => $v['total_operaciones'],
                    'volumen_usd'       => round($v['volumen_usd'], 2),
                ])
                ->sortByDesc('volumen_usd')
                ->values(),
            'efectivo_pendiente' => [
                'count'     => $efCount,
                'monto_usd' => round($efMonto, 2),
            ],
        ];
    }

    /**
     * Volumen en USD de una operación: usa el equivalente USD de la pata
     * no-local (USD/USDT/EUR/COP); si no hay, toma el mayor equivalente.
     */
    private function volumenUsdDeOperacion(Operacion $op): float
    {
        $asset = $op->movimientos->first(fn ($m) => $m->moneda?->codigo !== 'VES');

        if ($asset) {
            return abs((float) $asset->monto_usd_equivalente);
        }

        return (float) ($op->movimientos->max(fn ($m) => abs((float) $m->monto_usd_equivalente)) ?? 0.0);
    }
}
