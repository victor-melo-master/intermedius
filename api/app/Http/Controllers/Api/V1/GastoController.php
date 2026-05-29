<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gasto\StoreGastoRequest;
use App\Http\Resources\OperacionResource;
use App\Models\Operacion;
use App\Services\Operaciones\RegistroOperacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GastoController extends Controller
{
    public function __construct(private readonly RegistroOperacionService $registroService) {}

    /**
     * GET /api/v1/gastos — lista operaciones de tipo gasto, paginadas.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Operacion::class);

        $perPage = min((int) $request->get('per_page', 25), 100);

        $query = Operacion::with(['tipoOperacion', 'categoriaGasto', 'operador', 'movimientos.cuenta', 'movimientos.moneda'])
            ->whereHas('tipoOperacion', fn ($q) => $q->where('codigo', 'gasto'))
            ->when($request->filled('fecha_desde'),         fn ($q) => $q->where('fecha', '>=', $request->fecha_desde))
            ->when($request->filled('fecha_hasta'),         fn ($q) => $q->where('fecha', '<=', $request->fecha_hasta))
            ->when($request->filled('categoria_gasto_id'),  fn ($q) => $q->where('categoria_gasto_id', $request->categoria_gasto_id))
            ->when($request->filled('operador_id'),         fn ($q) => $q->where('operador_id', $request->operador_id))
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        return OperacionResource::collection($query->paginate($perPage));
    }

    /**
     * POST /api/v1/gastos — registra un gasto (tipo_codigo='gasto' inyectado).
     */
    public function store(StoreGastoRequest $request): JsonResponse
    {
        $operacion = $this->registroService->registrar($request->validated());

        return (new OperacionResource($operacion))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/v1/gastos/{operacion}
     */
    public function show(Operacion $operacion): OperacionResource
    {
        $this->authorize('view', $operacion);

        $operacion->load([
            'tipoOperacion',
            'categoriaGasto',
            'operador',
            'movimientos.cuenta.titular',
            'movimientos.moneda',
        ]);

        return new OperacionResource($operacion);
    }
}
