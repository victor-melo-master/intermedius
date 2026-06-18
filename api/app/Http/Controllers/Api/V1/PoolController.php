<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OperacionResource;
use App\Models\Operacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PoolController extends Controller
{
    /**
     * Relaciones cargadas para las órdenes del pool.
     */
    private const EAGER = [
        'cliente',
        'movimientos.cuenta.banco',
        'movimientos.moneda',
        'operador',
        'pagador',
    ];

    /**
     * GET /api/v1/pool
     * Lista las órdenes PENDIENTES del pool (sin asignar).
     * Más antiguas primero.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->get('per_page', 25), 100);

        $query = Operacion::pendientes()
            ->with(self::EAGER)
            ->orderBy('created_at')
            ->orderBy('id');

        return OperacionResource::collection($query->paginate($perPage));
    }

    /**
     * GET /api/v1/pool/mis-ordenes
     * Lista las órdenes ASIGNADAS al pagador autenticado.
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
     * POST /api/v1/pool/{operacion}/tomar
     * El pagador toma una orden pendiente.
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
     * POST /api/v1/pool/{operacion}/soltar
     * El pagador suelta la orden y vuelve al pool.
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
     * POST /api/v1/pool/{operacion}/pagar
     * El pagador marca la orden como pagada.
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
     * POST /api/v1/pool/{operacion}/cancelar
     * Solo admin|super_admin. Requiere motivo_cancelacion.
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
