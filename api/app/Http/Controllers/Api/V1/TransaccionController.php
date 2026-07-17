<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cuenta;
use App\Models\Operacion;
use App\Models\Transaccion;
use App\Services\Transaccion\SaldoValidator;
use App\Services\Transaccion\TransaccionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransaccionController extends Controller
{
    public function __construct(
        private readonly TransaccionService $transaccionService,
        private readonly SaldoValidator $saldoValidator,
    ) {}

    /**
     * Actualiza una transacción durante verificación (cambia cuentas).
     */
    public function update(Request $request, Operacion $operacion, Transaccion $transaccion): JsonResponse
    {
        $this->authorize('update', $operacion);

        if ($operacion->estatus !== 'en_verificacion') {
            return response()->json([
                'message' => 'La operación no está en proceso de verificación.',
            ], 422);
        }

        if ($transaccion->operacion_id !== $operacion->id) {
            return response()->json(['message' => 'La transacción no pertenece a esta operación.'], 404);
        }

        if ($transaccion->estado !== 'pendiente') {
            return response()->json([
                'message' => 'Solo se pueden editar transacciones en estado pendiente.',
            ], 422);
        }

        $request->validate([
            'cuenta_origen_id'  => 'sometimes|required|exists:cuentas,id,deleted_at,NULL',
            'cuenta_destino_id' => 'sometimes|required|exists:cuentas,id,deleted_at,NULL',
        ]);

        $cambios = [];

        if ($request->has('cuenta_origen_id') && $request->cuenta_origen_id != $transaccion->cuenta_origen_id) {
            $nuevaCuenta = Cuenta::findOrFail($request->cuenta_origen_id);

            if ($nuevaCuenta->moneda_id !== $transaccion->moneda_id) {
                return response()->json([
                    'message' => 'La cuenta de origen debe ser de la misma moneda que la transacción.',
                ], 422);
            }

            $this->transaccionService->cambiarCuentaOrigen($transaccion, $request->cuenta_origen_id);
            $cambios['cuenta_origen_id'] = [$transaccion->getOriginal('cuenta_origen_id'), $request->cuenta_origen_id];
        }

        if ($request->has('cuenta_destino_id') && $request->cuenta_destino_id != $transaccion->cuenta_destino_id) {
            $nuevaCuenta = Cuenta::findOrFail($request->cuenta_destino_id);

            if ($nuevaCuenta->moneda_id !== $transaccion->moneda_id) {
                return response()->json([
                    'message' => 'La cuenta de destino debe ser de la misma moneda que la transacción.',
                ], 422);
            }

            $this->transaccionService->cambiarCuentaDestino($transaccion, $request->cuenta_destino_id);
            $cambios['cuenta_destino_id'] = [$transaccion->getOriginal('cuenta_destino_id'), $request->cuenta_destino_id];
        }

        if (!empty($cambios)) {
            activity('verificacion')
                ->performedOn($transaccion)
                ->causedBy($request->user())
                ->withProperties([
                    'operacion_id' => $operacion->id,
                    'cambios'      => $cambios,
                ])
                ->event('cuenta_modificada')
                ->log('Cuenta modificada durante verificación');
        }

        return response()->json($transaccion->fresh(['cuentaOrigen.banco', 'cuentaDestino.banco', 'moneda']));
    }

    /**
     * Agrega una nueva transacción durante verificación.
     */
    public function store(Request $request, Operacion $operacion): JsonResponse
    {
        $this->authorize('update', $operacion);

        if ($operacion->estatus !== 'en_verificacion') {
            return response()->json([
                'message' => 'La operación no está en proceso de verificación.',
            ], 422);
        }

        $request->validate([
            'cuenta_origen_id'  => 'required|exists:cuentas,id,deleted_at,NULL',
            'cuenta_destino_id' => 'required|exists:cuentas,id,deleted_at,NULL',
            'moneda_id'         => 'required|exists:monedas,id',
            'monto'             => 'required|numeric|min:0.01',
        ]);

        $cuentaOrigen = Cuenta::findOrFail($request->cuenta_origen_id);
        $cuentaDestino = Cuenta::findOrFail($request->cuenta_destino_id);

        if ($cuentaOrigen->moneda_id != $request->moneda_id) {
            return response()->json([
                'message' => 'La cuenta de origen no pertenece a la moneda indicada.',
            ], 422);
        }

        if ($cuentaDestino->moneda_id != $request->moneda_id) {
            return response()->json([
                'message' => 'La cuenta de destino no pertenece a la moneda indicada.',
            ], 422);
        }

        $this->saldoValidator->assertSaldoSuficiente(
            $request->cuenta_origen_id,
            $request->moneda_id,
            (float) $request->monto
        );

        $maxOrden = $operacion->transacciones()->max('orden') ?? 0;

        $transaccion = Transaccion::create([
            'operacion_id'      => $operacion->id,
            'cuenta_origen_id'  => $request->cuenta_origen_id,
            'cuenta_destino_id' => $request->cuenta_destino_id,
            'moneda_id'         => $request->moneda_id,
            'monto'             => round((float) $request->monto, 2),
            'estado'            => 'pendiente',
            'orden'             => $maxOrden + 1,
        ]);

        activity('verificacion')
            ->performedOn($transaccion)
            ->causedBy($request->user())
            ->withProperties(['operacion_id' => $operacion->id])
            ->event('transaccion_agregada')
            ->log('Transacción agregada durante verificación');

        return response()->json(
            $transaccion->fresh(['cuentaOrigen.banco', 'cuentaDestino.banco', 'moneda']),
            201
        );
    }

    /**
     * Valida una transacción individual.
     */
    public function validar(Request $request, Operacion $operacion, Transaccion $transaccion): JsonResponse
    {
        $this->authorize('update', $operacion);

        if ($operacion->estatus !== 'en_verificacion') {
            return response()->json([
                'message' => 'La operación no está en proceso de verificación.',
            ], 422);
        }

        if ($transaccion->operacion_id !== $operacion->id) {
            return response()->json(['message' => 'La transacción no pertenece a esta operación.'], 404);
        }

        if ($transaccion->estado !== 'pendiente') {
            return response()->json([
                'message' => 'Solo se pueden validar transacciones en estado pendiente.',
            ], 422);
        }

        $this->transaccionService->validarTransaccion($transaccion, $request->user());

        activity('verificacion')
            ->performedOn($transaccion)
            ->causedBy($request->user())
            ->withProperties(['operacion_id' => $operacion->id])
            ->event('transaccion_validada')
            ->log('Transacción validada durante verificación');

        $todasValidadas = $operacion->transacciones()->where('estado', '!=', 'validada')->doesntExist();

        return response()->json([
            'transaccion'        => $transaccion->fresh(['cuentaOrigen.banco', 'cuentaDestino.banco', 'moneda']),
            'todas_validadas'    => $todasValidadas,
        ]);
    }

    /**
     * Elimina una transacción pendiente durante verificación.
     */
    public function destroy(Request $request, Operacion $operacion, Transaccion $transaccion): JsonResponse
    {
        $this->authorize('update', $operacion);

        if ($operacion->estatus !== 'en_verificacion') {
            return response()->json([
                'message' => 'La operación no está en proceso de verificación.',
            ], 422);
        }

        if ($transaccion->operacion_id !== $operacion->id) {
            return response()->json(['message' => 'La transacción no pertenece a esta operación.'], 404);
        }

        if ($transaccion->estado !== 'pendiente') {
            return response()->json([
                'message' => 'Solo se pueden eliminar transacciones en estado pendiente.',
            ], 422);
        }

        $transaccion->delete();

        activity('verificacion')
            ->performedOn($transaccion)
            ->causedBy($request->user())
            ->withProperties(['operacion_id' => $operacion->id])
            ->event('transaccion_eliminada')
            ->log('Transacción eliminada durante verificación');

        return response()->json(null, 204);
    }
}
