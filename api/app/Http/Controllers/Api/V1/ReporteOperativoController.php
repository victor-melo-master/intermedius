<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\ReporteOperativoExport;
use App\Http\Controllers\Controller;
use App\Services\Reportes\ReporteOperativoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controlador del reporte operativo (Resumen del período).
 * Genera el resumen agregado de operaciones y lo exporta a Excel o PDF.
 */
class ReporteOperativoController extends Controller
{
    public function __construct(private readonly ReporteOperativoService $service) {}

    /**
     * Genera el resumen operativo para un rango de fechas.
     *
     * @param Request $request Parámetros 'desde', 'hasta', 'moneda' y 'operador_id' (opcionales)
     * @return JsonResponse Resumen agregado del período
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date'],
            'moneda'      => ['nullable', 'string', 'max:10'],
            'operador_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $desde      = $request->input('fecha_desde', today()->toDateString());
        $hasta      = $request->input('fecha_hasta', today()->toDateString());

        $resumen = $this->service->resumen(
            $desde,
            $hasta,
            $request->input('moneda'),
            $request->input('operador_id')
        );

        return response()->json($resumen);
    }

    /**
     * Exporta el resumen operativo en formato Excel o PDF y lo persiste en storage.
     *
     * @param Request $request Parámetros 'desde', 'hasta', 'moneda'?, 'operador_id'? y 'formato' (excel|pdf)
     * @return JsonResponse Metadatos del archivo exportado
     */
    public function exportar(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date'],
            'moneda'      => ['nullable', 'string', 'max:10'],
            'operador_id' => ['nullable', 'integer', 'exists:users,id'],
            'formato'     => ['required', 'in:excel,pdf'],
        ]);

        $desde  = Carbon::parse($request->input('fecha_desde', today()->toDateString()))->startOfDay();
        $hasta  = Carbon::parse($request->input('fecha_hasta', today()->toDateString()))->endOfDay();
        $moneda = $request->input('moneda');
        $operadorId = $request->input('operador_id');
        $formato = $request->input('formato');

        $resumen = $this->service->resumen($desde->toDateString(), $hasta->toDateString(), $moneda, $operadorId);

        $dir      = config('reportes.resumen.storage_path', 'reportes/resumen');
        $slug     = $desde->format('Y-m') . ($moneda ? '-' . strtolower($moneda) : '');
        $ext      = $formato === 'excel' ? 'xlsx' : 'pdf';
        $nombre   = "resumen_operativo_{$slug}.{$ext}";
        $path     = "{$dir}/{$nombre}";

        if ($formato === 'excel') {
            Excel::store(new ReporteOperativoExport($resumen), $path);
        } else {
            $pdf = Pdf::loadView('reportes.resumen_operativo', [
                'resumen' => $resumen,
                'desde'   => $desde,
                'hasta'   => $hasta,
                'moneda'  => $moneda,
            ]);
            Storage::put($path, $pdf->output());
        }

        return response()->json([
            'data' => [
                'path'        => $path,
                'nombre'      => $nombre,
                'formato'     => $formato,
                'tipo'        => 'resumen',
                'generado_en' => now()->toIso8601String(),
            ],
        ]);
    }
}
