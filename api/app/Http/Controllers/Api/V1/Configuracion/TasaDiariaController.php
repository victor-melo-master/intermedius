<?php

namespace App\Http\Controllers\Api\V1\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreTasaDiariaRequest;
use App\Models\Moneda;
use App\Models\TasaDiaria;
use App\Services\Configuracion\TasaDiariaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TasaDiariaController extends Controller
{
    public function __construct(private readonly TasaDiariaService $service) {}

    public function index(Request $request): JsonResponse
    {
        $fecha = $request->date('fecha', 'Y-m-d') ?? now()->toDateString();

        $query = TasaDiaria::with(['monedaBase', 'monedaCotizada', 'definidaPor'])
            ->whereDate('fecha', $fecha)
            ->orderByDesc('vigente_desde');

        if ($request->filled('moneda_base_id')) {
            $query->where('moneda_base_id', $request->integer('moneda_base_id'));
        }
        if ($request->filled('moneda_cotizada_id')) {
            $query->where('moneda_cotizada_id', $request->integer('moneda_cotizada_id'));
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(StoreTasaDiariaRequest $request): JsonResponse
    {
        $tasa = $this->service->publicar($request->validated(), $request->user());

        $tasa->load(['monedaBase', 'monedaCotizada', 'definidaPor']);

        return response()->json(['data' => $tasa], 201);
    }

    public function vigentes(): JsonResponse
    {
        $tasas = TasaDiaria::with(['monedaBase', 'monedaCotizada', 'definidaPor'])
            ->whereNull('vigente_hasta')
            ->orderBy('moneda_base_id')
            ->get()
            ->map(fn (TasaDiaria $t) => [
                'id'                 => $t->id,
                'par'                => "{$t->monedaBase->codigo}/{$t->monedaCotizada->codigo}",
                'tasa_compra'        => (string) $t->tasa_compra,
                'tasa_compra_minima' => $t->tasa_compra_minima !== null ? (string) $t->tasa_compra_minima : null,
                'tasa_venta'         => (string) $t->tasa_venta,
                'tasa_venta_minima'  => $t->tasa_venta_minima !== null ? (string) $t->tasa_venta_minima : null,
                'definida_a_las'=> $t->vigente_desde?->toIso8601String(),
                'vigente_desde' => $t->vigente_desde?->toIso8601String(),
                'definida_por'  => $t->definidaPor?->name,
                'moneda_base_id'    => $t->moneda_base_id,
                'moneda_cotizada_id'=> $t->moneda_cotizada_id,
            ]);

        return response()->json(['data' => $tasas]);
    }

    public function historial(int $base, int $cotizada): JsonResponse
    {
        $tasas = TasaDiaria::with(['definidaPor'])
            ->where('moneda_base_id', $base)
            ->where('moneda_cotizada_id', $cotizada)
            ->orderByDesc('vigente_desde')
            ->paginate(50);

        return response()->json($tasas);
    }
}
