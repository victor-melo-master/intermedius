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
            'movimientos.cuenta.banco',
            'movimientos.moneda',
            'movimientos.validadaPor',
            'tipoOperacion',
            'cliente',
            'operador',
        ]);

        $saldos = (object) [];
        $cuentasIds = $operacion->movimientos->pluck('cuenta_id')->unique()->values();

        foreach ($cuentasIds as $cuentaId) {
            $cuenta = \App\Models\Cuenta::with('moneda')->find($cuentaId);
            if ($cuenta) {
                $saldos->$cuentaId = [
                    'alias'  => $cuenta->alias,
                    'saldo'  => round($this->saldoValidator->obtenerSaldoDisponible($cuenta, $cuenta->moneda_id), 2),
                    'moneda' => $cuenta->moneda->codigo,
                ];
            }
        }

        $totalMovimientos = $operacion->movimientos->count();
        $movimientosValidados = $operacion->movimientos->where('estado', 'validada')->count();

        return response()->json([
            'operacion'              => $operacion,
            'movimientos'            => $operacion->movimientos,
            'saldos'                 => $saldos,
            'total_movimientos'      => $totalMovimientos,
            'movimientos_validados'  => $movimientosValidados,
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

        $pendientes = $operacion->movimientos()->where('estado', '!=', 'validada')->count();

        if ($pendientes > 0) {
            return response()->json([
                'message' => "Hay {$pendientes} movimiento(s) sin validar. Todos deben estar validados para cerrar la verificación.",
                'movimientos_pendientes' => $pendientes,
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
     * Valida un movimiento individual durante la verificación.
     */
    public function validarMovimiento(Request $request, Operacion $operacion, \App\Models\Movimiento $movimiento): JsonResponse
    {
        $this->authorize('update', $operacion);

        if ($operacion->estatus !== 'en_verificacion') {
            return response()->json(['message' => 'La operación no está en proceso de verificación.'], 422);
        }

        if ($movimiento->operacion_id !== $operacion->id) {
            return response()->json(['message' => 'El movimiento no pertenece a esta operación.'], 404);
        }

        if ($movimiento->estado !== 'pendiente') {
            return response()->json(['message' => 'Solo se pueden validar movimientos en estado pendiente.'], 422);
        }

        $movimiento->update([
            'estado'          => 'validada',
            'validada_en'     => now(),
            'validada_por_id' => $request->user()->id,
        ]);

        activity('verificacion')
            ->performedOn($movimiento)
            ->causedBy($request->user())
            ->withProperties(['operacion_id' => $operacion->id])
            ->event('movimiento_validado')
            ->log('Movimiento validado durante verificación');

        $todasValidados = $operacion->movimientos()->where('estado', '!=', 'validada')->doesntExist();

        return response()->json([
            'movimiento'       => $movimiento->fresh(['cuenta.banco', 'moneda', 'validadaPor']),
            'todas_validados'  => $todasValidados,
        ]);
    }

    /**
     * Rechaza un movimiento individual durante la verificación.
     */
    public function rechazarMovimiento(Request $request, Operacion $operacion, \App\Models\Movimiento $movimiento): JsonResponse
    {
        $this->authorize('update', $operacion);

        if ($operacion->estatus !== 'en_verificacion') {
            return response()->json(['message' => 'La operación no está en proceso de verificación.'], 422);
        }

        if ($movimiento->operacion_id !== $operacion->id) {
            return response()->json(['message' => 'El movimiento no pertenece a esta operación.'], 404);
        }

        if ($movimiento->estado !== 'pendiente') {
            return response()->json(['message' => 'Solo se pueden rechazar movimientos en estado pendiente.'], 422);
        }

        $request->validate([
            'motivo_rechazo' => 'required|string|max:500',
        ]);

        $movimiento->update([
            'estado'           => 'rechazada',
            'motivo_rechazo'   => $request->motivo_rechazo,
        ]);

        activity('verificacion')
            ->performedOn($movimiento)
            ->causedBy($request->user())
            ->withProperties([
                'operacion_id'     => $operacion->id,
                'motivo_rechazo'   => $request->motivo_rechazo,
            ])
            ->event('movimiento_rechazado')
            ->log('Movimiento rechazado durante verificación');

        return response()->json([
            'movimiento' => $movimiento->fresh(['cuenta.banco', 'moneda', 'validadaPor']),
        ]);
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
