<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Reportes\ReporteComisionesOperadoresService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Carbon;

/**
 * Controlador de reportes de comisiones de operadores.
 * Genera, exporta y lista el histórico de comisiones calculadas.
 */
class ReporteComisionesController extends Controller
{
    public function __construct(private readonly ReporteComisionesOperadoresService $service) {}

    /**
     * Genera el reporte de comisiones para un rango de fechas.
     *
     * @param Request $request Parámetros 'desde' y 'hasta' (fechas)
     * @return JsonResponse Datos del reporte generado
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
        ]);

        $desde = Carbon::parse($request->input('desde'))->startOfDay();
        $hasta = Carbon::parse($request->input('hasta'))->endOfDay();

        $reporte = $this->service->generar($desde, $hasta);

        return response()->json(['data' => $reporte]);
    }

    /**
     * Exporta el reporte de comisiones en formato Excel o PDF.
     *
     * @param Request $request Parámetros 'desde', 'hasta' y 'formato' (excel|pdf)
     * @return JsonResponse|StreamedResponse URL del archivo exportado
     */
    public function exportar(Request $request): JsonResponse|StreamedResponse
    {
        $request->validate([
            'desde'   => ['required', 'date'],
            'hasta'   => ['required', 'date', 'after_or_equal:desde'],
            'formato' => ['required', 'in:excel,pdf'],
        ]);

        $desde   = Carbon::parse($request->input('desde'))->startOfDay();
        $hasta   = Carbon::parse($request->input('hasta'))->endOfDay();
        $formato = $request->input('formato');

        $path = $formato === 'excel'
            ? $this->service->exportarExcel($desde, $hasta)
            : $this->service->exportarPdf($desde, $hasta);

        return response()->json([
            'data' => [
                'path'      => $path,
                'url'       => Storage::url($path),
                'formato'   => $formato,
                'generado_en' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Lista el histórico de reportes exportados con sus metadatos.
     *
     * @return JsonResponse Lista de archivos ordenados por fecha de modificación
     */
    public function historico(): JsonResponse
    {
        $base  = config('reportes.comisiones_operadores.storage_path');
        $files = collect(Storage::files($base))
            ->map(fn ($f) => [
                'path'          => $f,
                'url'           => Storage::url($f),
                'nombre'        => basename($f),
                'tamano_bytes'  => Storage::size($f),
                'modificado_en' => Carbon::createFromTimestamp(Storage::lastModified($f))->toIso8601String(),
            ])
            ->sortByDesc('modificado_en')
            ->values();

        return response()->json(['data' => $files]);
    }
}
