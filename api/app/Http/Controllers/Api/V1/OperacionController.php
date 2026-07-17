<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacion\StoreOperacionRequest;
use App\Http\Requests\Operacion\UpdateOperacionRequest;
use App\Http\Requests\Operacion\VerificarOperacionRequest;
use App\Http\Resources\OperacionResource;
use App\Models\Operacion;
use App\Services\Operaciones\RegistroOperacionService;
use App\Services\Transaccion\SaldoValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador de operaciones financieras.
 * CRUD de operaciones con soporte de filtros, verificación y registro.
 */
class OperacionController extends Controller
{
    public function __construct(
        private readonly RegistroOperacionService $registroService,
        private readonly SaldoValidator $saldoValidator,
    ) {}

    /**
     * Lista paginada de operaciones con filtros opcionales.
     *
     * @param Request $request Filtros: fecha_desde, fecha_hasta, tipo_codigo, cliente_id, operador_id, estatus, cuenta_id
     * @return AnonymousResourceCollection Colección paginada de operaciones
     */
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

    /**
     * Registra una nueva operación en el sistema.
     *
     * @param StoreOperacionRequest $request Datos validados de la operación
     * @return JsonResponse Operación creada con código 201
     */
    public function store(StoreOperacionRequest $request): JsonResponse
    {
        $operacion = $this->registroService->registrar($request->validated());

        return (new OperacionResource($operacion))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Actualiza una operación existente.
     *
     * @param UpdateOperacionRequest $request Datos validados de actualización
     * @param Operacion $operacion Operación a modificar
     * @return JsonResponse Operación actualizada
     */
    public function update(UpdateOperacionRequest $request, Operacion $operacion): JsonResponse
    {
        $operacion = $this->registroService->actualizar($operacion, $request->validated(), $request->user());

        return (new OperacionResource($operacion))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Muestra los detalles de una operación con todas sus relaciones.
     *
     * @param Operacion $operacion Operación a consultar
     * @return OperacionResource Recurso con datos completos de la operación
     */
    public function show(Operacion $operacion): OperacionResource
{
    $this->authorize('view', $operacion);

    // \Log::info('Show operacion', [
    //     'id' => $operacion->id,
    //     'fecha' => $operacion->fecha,
    //     'estatus' => $operacion->estatus,
    //     'tasa' => $operacion->tasa_aplicada,
    // ]);

    $operacion->load([
        'movimientos.cuenta.titular',
        'movimientos.moneda',
        'tipoOperacion',
        'cliente',
        'clienteEmisor',
        'clienteReceptor',
        'categoriaGasto',
        'operador',
        'verificadoPor',
        'pagador',
    ]);

    // \Log::info('Show operacion after load', [
    //     'movimientos_count' => $operacion->movimientos->count(),
    //     'tipo_operacion' => $operacion->tipoOperacion?->codigo,
    // ]);

    return new OperacionResource($operacion);
}

    /**
     * Retorna la vista de verificación de una operación con sus transacciones y saldos.
     */
    public function verificacion(Operacion $operacion): JsonResponse
    {
        $this->authorize('view', $operacion);

        $operacion->load([
            'transacciones.cuentaOrigen.banco',
            'transacciones.cuentaDestino.banco',
            'transacciones.moneda',
            'transacciones.validadaPor',
            'tipoOperacion',
            'cliente',
            'operador',
        ]);

        $saldos = [];
        $cuentasIds = $operacion->transacciones->pluck('cuenta_origen_id')
            ->merge($operacion->transacciones->pluck('cuenta_destino_id'))
            ->unique()
            ->values();

        foreach ($cuentasIds as $cuentaId) {
            $cuenta = \App\Models\Cuenta::with('moneda')->find($cuentaId);
            if ($cuenta) {
                $saldos[$cuentaId] = [
                    'alias'  => $cuenta->alias,
                    'saldo'  => round($this->saldoValidator->obtenerSaldoDisponible($cuenta, $cuenta->moneda_id), 2),
                    'moneda' => $cuenta->moneda->codigo,
                ];
            }
        }

        return response()->json([
            'operacion'     => $operacion,
            'saldos'        => $saldos,
            'total_transacciones' => $operacion->transacciones->count(),
            'transacciones_validadas' => $operacion->transacciones->where('estado', 'validada')->count(),
        ]);
    }

    /**
     * Inicia el proceso de verificación de una operación.
     */
    public function iniciarVerificacion(Operacion $operacion): JsonResponse
    {
        $this->authorize('update', $operacion);

        if ($operacion->estatus !== 'sin_verificar') {
            return response()->json([
                'message' => 'La operación no está en estado sin_verificar.',
            ], 422);
        }

        $operacion->update(['estatus' => 'en_verificacion']);

        activity('verificacion')
            ->performedOn($operacion)
            ->causedBy(\Auth::user())
            ->event('verificacion_iniciada')
            ->log('Proceso de verificación iniciado');

        return response()->json([
            'message' => 'Verificación iniciada.',
            'operacion' => $operacion->fresh(['tipoOperacion', 'operador']),
        ]);
    }

    /**
     * Cierra la verificación de una operación (todas las transacciones deben estar validadas).
     */
    public function verificar(VerificarOperacionRequest $request, Operacion $operacion): JsonResponse
    {
        if ($operacion->estatus !== 'en_verificacion') {
            return response()->json([
                'message' => 'La operación no está en proceso de verificación.',
            ], 422);
        }

        $pendientes = $operacion->transacciones()->where('estado', '!=', 'validada')->count();

        if ($pendientes > 0) {
            return response()->json([
                'message' => "Hay {$pendientes} transacción(es) sin validar. Todas deben estar validadas para cerrar la verificación.",
                'transacciones_pendientes' => $pendientes,
            ], 422);
        }

        $operacion->update([
            'estatus'           => 'verificado',
            'verificado_at'     => now(),
            'verificado_por_id' => $request->user()->id,
        ]);

        activity('verificacion')
            ->performedOn($operacion)
            ->causedBy($request->user())
            ->event('verificacion_completada')
            ->log('Verificación completada');

        return (new OperacionResource($operacion->fresh(['tipoOperacion', 'operador', 'verificadoPor'])))->response();
    }

    /**
     * Bloquea la eliminación de operaciones (no permitido).
     *
     * @param Operacion $operacion Operación que se intenta eliminar
     * @return JsonResponse Respuesta 405 (Método no permitido)
     */
    public function destroy(Operacion $operacion): JsonResponse
    {
        return response()->json([
            'message' => 'Las operaciones no se eliminan. Use ajuste manual para corregir.',
        ], 405);
    }
}
