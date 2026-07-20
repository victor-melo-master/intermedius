<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OperacionResource;
use App\Models\Operacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador del pool de órdenes para pagadores.
 * Gestiona la asignación, liberación, pago y cancelación de órdenes pendientes.
 */
class PoolController extends Controller
{
    /**
     * Relaciones cargadas para las órdenes del pool.
     */
    private const EAGER = [
        'cliente',
        'tipoOperacion',
        'movimientos.cuenta.banco',
        'movimientos.cuenta.titular',
        'movimientos.cuenta.cliente',
        'movimientos.moneda',
        'transacciones.cuentaOrigen.banco',
        'transacciones.cuentaDestino.banco',
        'transacciones.moneda',
        'operador',
        'pagador',
    ];

    /**
     * Lista las órdenes pendientes del pool (sin asignar), más antiguas primero.
     *
     * @param Request $request Parámetro opcional 'per_page' para paginación
     * @return AnonymousResourceCollection Colección paginada de órdenes pendientes
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->get('per_page', 25), 100);

        $query = Operacion::pendientes()
            ->where('estado', '!=', 'solicitud')
            ->with(self::EAGER)
            ->orderBy('created_at')
            ->orderBy('id');

        return OperacionResource::collection($query->paginate($perPage));
    }

    /**
     * Lista las órdenes asignadas al pagador autenticado.
     *
     * @param Request $request Parámetro opcional 'per_page' para paginación
     * @return AnonymousResourceCollection Colección paginada de órdenes asignadas
     */
    public function misOrdenes(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->get('per_page', 25), 100);

        $query = Operacion::asignadasA($request->user()->id)
            ->with(self::EAGER)
            ->orderByDesc('asignada_at')
            ->orderByDesc('id');

        return OperacionResource::collection($query->paginate($perPage));
    }

    /**
     * Asigna una orden pendiente al pagador autenticado.
     *
     * @param Request $request Datos de la solicitud (usuario autenticado)
     * @param Operacion $operacion Orden a tomar
     * @return JsonResponse Orden asignada o error 422 si ya fue tomada
     */
    public function tomar(Request $request, Operacion $operacion): JsonResponse
    {
        if ($operacion->estado_pool !== 'pendiente') {
            return response()->json([
                'message' => 'Esta orden ya fue tomada por otro pagador.',
            ], 422);
        }

        $operacion->update([
            'estado_pool' => 'asignada',
            'pagador_id'  => $request->user()->id,
            'asignada_at' => now(),
        ]);

        return (new OperacionResource($operacion->fresh(self::EAGER)))->response();
    }

    /**
     * Libera una orden asignada y la devuelve al pool como pendiente.
     *
     * @param Request $request Datos de la solicitud (usuario autenticado)
     * @param Operacion $operacion Orden a liberar
     * @return JsonResponse Orden liberada o error 403/422
     */
    public function soltar(Request $request, Operacion $operacion): JsonResponse
    {
        if (! $this->puedeGestionar($request, $operacion)) {
            return response()->json([
                'message' => 'Solo puede soltar órdenes asignadas a usted.',
            ], 403);
        }

        if ($operacion->estado_pool !== 'asignada') {
            return response()->json([
                'message' => 'Solo se pueden soltar órdenes asignadas.',
            ], 422);
        }

        $operacion->update([
            'estado_pool' => 'pendiente',
            'pagador_id'  => null,
            'asignada_at' => null,
        ]);

        return (new OperacionResource($operacion->fresh(self::EAGER)))->response();
    }

    /**
     * Marca una orden asignada como pagada.
     *
     * @param Request $request Datos de la solicitud (usuario autenticado)
     * @param Operacion $operacion Orden a marcar como pagada
     * @return JsonResponse Orden actualizada o error 403/422
     */
    public function marcarPagada(Request $request, Operacion $operacion): JsonResponse
    {
        if (! $this->puedeGestionar($request, $operacion)) {
            return response()->json([
                'message' => 'Solo puede pagar órdenes asignadas a usted.',
            ], 403);
        }

        if ($operacion->estado_pool !== 'asignada') {
            return response()->json([
                'message' => 'Solo se pueden pagar órdenes asignadas.',
            ], 422);
        }

        $operacion->update([
            'estado_pool' => 'pagada',
            'pagada_at'   => now(),
        ]);

        return (new OperacionResource($operacion->fresh(self::EAGER)))->response();
    }

    /**
     * Cancela una orden (solo admin/super_admin). Requiere motivo de cancelación.
     *
     * @param Request $request Debe incluir 'motivo_cancelacion'
     * @param Operacion $operacion Orden a cancelar
     * @return JsonResponse Orden cancelada o error 422
     */
    public function cancelar(Request $request, Operacion $operacion): JsonResponse
    {
        $validated = $request->validate([
            'motivo_cancelacion' => ['required', 'string', 'max:1000'],
        ]);

        if ($operacion->estado_pool === 'cancelada') {
            return response()->json([
                'message' => 'Esta orden ya está cancelada.',
            ], 422);
        }

        $operacion->update([
            'estado_pool'        => 'cancelada',
            'cancelada_at'       => now(),
            'motivo_cancelacion' => $validated['motivo_cancelacion'],
        ]);

        return (new OperacionResource($operacion->fresh(self::EAGER)))->response();
    }

    /**
     * El usuario es admin/super_admin, o es el pagador asignado a la orden.
     */
    private function puedeGestionar(Request $request, Operacion $operacion): bool
    {
        $user = $request->user();

        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        return $operacion->pagador_id === $user->id;
    }
}
