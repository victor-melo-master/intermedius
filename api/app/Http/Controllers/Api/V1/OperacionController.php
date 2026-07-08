<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacion\StoreOperacionRequest;
use App\Http\Requests\Operacion\UpdateOperacionRequest;
use App\Http\Requests\Operacion\VerificarOperacionRequest;
use App\Http\Resources\OperacionResource;
use App\Models\Operacion;
use App\Services\Operaciones\RegistroOperacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OperacionController extends Controller
{
    public function __construct(private readonly RegistroOperacionService $registroService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Operacion::class);

        $perPage = min((int) $request->get('per_page', 25), 100);

        $query = Operacion::with(['tipoOperacion', 'cliente', 'operador', 'movimientos.moneda'])
            ->when($request->filled('fecha_desde'), fn ($q) => $q->where('fecha', '>=', $request->fecha_desde))
            ->when($request->filled('fecha_hasta'), fn ($q) => $q->where('fecha', '<=', $request->fecha_hasta))
            ->when($request->filled('tipo_codigo'),  fn ($q) => $q->whereHas('tipoOperacion', fn ($t) => $t->where('codigo', $request->tipo_codigo)))
            ->when($request->filled('cliente_id'),   fn ($q) => $q->where('cliente_id', $request->cliente_id))
            ->when($request->filled('operador_id'),  fn ($q) => $q->where('operador_id', $request->operador_id))
            ->when($request->filled('estatus'),      fn ($q) => $q->where('estatus', $request->estatus))
            ->when($request->filled('cuenta_id'),    fn ($q) => $q->whereHas('movimientos', fn ($m) => $m->where('cuenta_id', $request->cuenta_id)))
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        return OperacionResource::collection($query->paginate($perPage));
    }

    public function store(StoreOperacionRequest $request): JsonResponse
    {
        $operacion = $this->registroService->registrar($request->validated());

        return (new OperacionResource($operacion))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateOperacionRequest $request, Operacion $operacion): JsonResponse
    {
        $operacion = $this->registroService->actualizar($operacion, $request->validated(), $request->user());

        return (new OperacionResource($operacion))
            ->response()
            ->setStatusCode(200);
    }

    public function show(Operacion $operacion): OperacionResource
{
    $this->authorize('view', $operacion);

    \Log::info('Show operacion', [
        'id' => $operacion->id,
        'fecha' => $operacion->fecha,
        'estatus' => $operacion->estatus,
        'tasa' => $operacion->tasa_aplicada,
    ]);

    $operacion->load([
        'movimientos.cuenta.titular',
        'movimientos.moneda',
        'tipoOperacion',
        'cliente',
        'categoriaGasto',
        'operador',
        'verificadoPor',
        'pagador',
    ]);

    \Log::info('Show operacion after load', [
        'movimientos_count' => $operacion->movimientos->count(),
        'tipo_operacion' => $operacion->tipoOperacion?->codigo,
    ]);

    return new OperacionResource($operacion);
}

    public function verificar(VerificarOperacionRequest $request, Operacion $operacion): JsonResponse
    {
        if ($operacion->estatus === 'verificado') {
            return response()->json([
                'message' => 'La operación ya está verificada.',
            ], 422);
        }

        $operacion->update([
            'estatus'           => 'verificado',
            'verificado_at'     => now(),
            'verificado_por_id' => $request->user()->id,
        ]);

        return (new OperacionResource($operacion->fresh(['tipoOperacion', 'operador', 'verificadoPor'])))->response();
    }

    public function destroy(Operacion $operacion): JsonResponse
    {
        return response()->json([
            'message' => 'Las operaciones no se eliminan. Use ajuste manual para corregir.',
        ], 405);
    }
}
