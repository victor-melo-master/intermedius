<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Reportes\ReporteComisionesOperadoresService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controlador de reportes de comisiones de operadores.
 * Genera, exporta, descarga y lista el histórico de reportes generados.
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
     * Escanea las bases de comisiones y de resumen operativo.
     *
     * @return JsonResponse Lista de archivos ordenados por fecha de modificación
     */
    public function historico(): JsonResponse
    {
        $bases = [
            'comisiones' => config('reportes.comisiones_operadores.storage_path', 'reportes/comisiones'),
            'resumen'    => config('reportes.resumen.storage_path', 'reportes/resumen'),
        ];

        $files = new Collection();

        foreach ($bases as $tipo => $base) {
            foreach (Storage::files($base) as $f) {
                $files->push([
                    'path'          => $f,
                    'url'           => null,
                    'nombre'        => basename($f),
                    'tipo'          => $tipo,
                    'formato'       => strtolower(pathinfo($f, PATHINFO_EXTENSION)),
                    'tamano_bytes'  => Storage::size($f),
                    'modificado_en' => Carbon::createFromTimestamp(Storage::lastModified($f))->toIso8601String(),
                ]);
            }
        }

        $files = $files->sortByDesc('modificado_en')->values();

        return response()->json(['data' => $files]);
    }

    /**
     * Descarga un archivo de reporte desde el storage protegido.
     * Valida el path contra un whitelist de bases y extensiones (bloquea traversal).
     *
     * @param Request $request Parámetro 'path' (ruta relativa en storage)
     * @return StreamedResponse Archivo descargado
     */
    public function descargar(Request $request): StreamedResponse
    {
        $request->validate([
            'path' => ['required', 'string', 'max:255'],
        ]);

        $path = str_replace('\\', '/', (string) $request->input('path'));

        $pathPermitido = ! str_starts_with($path, '/')
            && ! str_contains($path, '..')
            && preg_match('#^reportes/(comisiones|resumen)/[A-Za-z0-9_\-\.]+\.(pdf|xlsx)$#', $path) === 1;

        if (! $pathPermitido || ! Storage::exists($path)) {
            abort(404, 'Reporte no encontrado.');
        }

        return Storage::download($path, basename($path));
    }
}
