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

/**
 * Controlador de gastos (operaciones de tipo 'gasto').
 * Lista, registra y consulta gastos del sistema.
 */
class GastoController extends Controller
{
    public function __construct(private readonly RegistroOperacionService $registroService) {}

    /**
     * Lista paginada de operaciones de tipo gasto con filtros opcionales.
     *
     * @param Request $request Filtros: fecha_desde, fecha_hasta, categoria_gasto_id, operador_id
     * @return AnonymousResourceCollection Colección paginada de gastos
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
     * Registra un nuevo gasto en el sistema.
     *
     * @param StoreGastoRequest $request Datos validados del gasto
     * @return JsonResponse Gasto creado con código 201
     */
    public function store(StoreGastoRequest $request): JsonResponse
    {
        $operacion = $this->registroService->registrar($request->validated());

        return (new OperacionResource($operacion))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Muestra los detalles de un gasto específico.
     *
     * @param Operacion $operacion Gasto a consultar
     * @return OperacionResource Recurso con datos del gasto
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
