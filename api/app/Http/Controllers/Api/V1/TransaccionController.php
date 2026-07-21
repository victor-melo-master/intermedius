<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cuenta;
use App\Models\Moneda;
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
     * Agrega una transacción a una operación.
     * Soporta ambos flujos: verificación (estatus) y multi-paso (estado).
     */
    public function store(Request $request, Operacion $operacion): JsonResponse
    {
        $this->authorize('update', $operacion);

        if (! $this->estaEnFlujoActivo($operacion)) {
            return response()->json([
                'message' => 'La operación no está en un estado que permita agregar transacciones.',
            ], 422);
        }

        $request->validate([
            'cuenta_origen_id'  => 'required|exists:cuentas,id,deleted_at,NULL',
            'cuenta_destino_id' => 'required|exists:cuentas,id,deleted_at,NULL',
            'moneda_id'         => 'required|exists:monedas,id',
            'monto'             => 'required|numeric|min:0.01',
            'tasa_aplicada'     => 'nullable|numeric|min:0',
            'tasas_snapshot'    => 'nullable|array',
            'metodo_pago'       => 'nullable|string|max:50',
            'comprobante'       => 'nullable|string|max:255',
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

        // Validar que las transacciones no excedan el monto solicitado
        if ($operacion->monto_solicitado && $operacion->tasa_aplicada) {
            $moneda = Moneda::find($request->moneda_id);
            $limite = $moneda->codigo === 'VES'
                ? round((float) $operacion->monto_solicitado * (float) $operacion->tasa_aplicada, 2)
                : (float) $operacion->monto_solicitado;

            $totalExistente = (float) $operacion->transacciones()
                ->where('moneda_id', $request->moneda_id)
                ->whereIn('estado', ['pendiente', 'confirmada', 'validada'])
                ->sum('monto');

            $montoNuevo = round((float) $request->monto, 2);

            if ($totalExistente + $montoNuevo > $limite) {
                $disponible = round($limite - $totalExistente, 2);
                return response()->json([
                    'message' => "El monto excede el límite de {$limite} {$moneda->codigo}. Disponible: {$disponible} {$moneda->codigo}.",
                ], 422);
            }
        }

        $maxOrden = $operacion->transacciones()->max('orden') ?? 0;

        $transaccion = Transaccion::create([
            'operacion_id'      => $operacion->id,
            'cuenta_origen_id'  => $request->cuenta_origen_id,
            'cuenta_destino_id' => $request->cuenta_destino_id,
            'moneda_id'         => $request->moneda_id,
            'monto'             => round((float) $request->monto, 2),
            'tasa_aplicada'     => $request->tasa_aplicada,
            'tasas_snapshot'    => $request->tasas_snapshot,
            'metodo_pago'       => $request->metodo_pago,
            'comprobante'       => $request->comprobante,
            'estado'            => 'pendiente',
            'orden'             => $maxOrden + 1,
        ]);

        activity('transacciones')
            ->performedOn($transaccion)
            ->causedBy($request->user())
            ->withProperties(['operacion_id' => $operacion->id])
            ->event('transaccion_agregada')
            ->log('Transacción agregada');

        return response()->json(
            $transaccion->fresh(['cuentaOrigen.banco', 'cuentaDestino.banco', 'moneda']),
            201
        );
    }

    /**
     * Actualiza una transacción pendiente.
     * Soporta ambos flujos: verificación y multi-paso.
     */
    public function update(Request $request, Operacion $operacion, Transaccion $transaccion): JsonResponse
    {
        $this->authorize('update', $operacion);

        if (! $this->estaEnFlujoActivo($operacion)) {
            return response()->json([
                'message' => 'La operación no está en un estado que permita editar transacciones.',
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
            'cuenta_origen_id'  => 'sometimes|exists:cuentas,id,deleted_at,NULL',
            'cuenta_destino_id' => 'sometimes|exists:cuentas,id,deleted_at,NULL',
            'monto'             => 'sometimes|numeric|min:0.01',
            'tasa_aplicada'     => 'nullable|numeric|min:0',
            'tasas_snapshot'    => 'nullable|array',
            'metodo_pago'       => 'nullable|string|max:50',
            'comprobante'       => 'nullable|string|max:255',
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

        $camposExtra = ['monto', 'tasa_aplicada', 'tasas_snapshot', 'metodo_pago', 'comprobante'];
        foreach ($camposExtra as $campo) {
            if ($request->has($campo) && $request->input($campo) != $transaccion->$campo) {
                // Validar límite si cambia el monto
                if ($campo === 'monto' && $operacion->monto_solicitado && $operacion->tasa_aplicada) {
                    $moneda = Moneda::find($transaccion->moneda_id);
                    $limite = $moneda->codigo === 'VES'
                        ? round((float) $operacion->monto_solicitado * (float) $operacion->tasa_aplicada, 2)
                        : (float) $operacion->monto_solicitado;

                    $totalExistente = (float) $operacion->transacciones()
                        ->where('moneda_id', $transaccion->moneda_id)
                        ->whereIn('estado', ['pendiente', 'confirmada', 'validada'])
                        ->where('id', '!=', $transaccion->id)
                        ->sum('monto');

                    $nuevoMonto = round((float) $request->input('monto'), 2);

                    if ($totalExistente + $nuevoMonto > $limite) {
                        $disponible = round($limite - $totalExistente, 2);
                        return response()->json([
                            'message' => "El monto excede el límite de {$limite} {$moneda->codigo}. Disponible: {$disponible} {$moneda->codigo}.",
                        ], 422);
                    }
                }

                $transaccion->update([$campo => $request->input($campo)]);
                $cambios[$campo] = true;
            }
        }

        if (!empty($cambios)) {
            activity('transacciones')
                ->performedOn($transaccion)
                ->causedBy($request->user())
                ->withProperties([
                    'operacion_id' => $operacion->id,
                    'cambios'      => $cambios,
                ])
                ->event('transaccion_modificada')
                ->log('Transacción modificada');
        }

        return response()->json($transaccion->fresh(['cuentaOrigen.banco', 'cuentaDestino.banco', 'moneda']));
    }

    /**
     * Confirma una transacción (nuevo flujo multi-paso): valida saldo y descuenta.
     */
    public function confirmar(Request $request, Operacion $operacion, Transaccion $transaccion): JsonResponse
    {
        $this->authorize('update', $operacion);

        if ($transaccion->operacion_id !== $operacion->id) {
            return response()->json(['message' => 'La transacción no pertenece a esta operación.'], 404);
        }

        if ($transaccion->estado !== 'pendiente') {
            return response()->json([
                'message' => 'Solo se pueden confirmar transacciones en estado pendiente.',
            ], 422);
        }

        if (($transaccion->metodo_pago ?? '') !== 'efectivo' && empty($transaccion->comprobante)) {
            return response()->json([
                'message' => 'Debe adjuntar comprobante para métodos de pago que no sean efectivo.',
            ], 422);
        }

        $transaccion = $this->transaccionService->confirmarTransaccion($transaccion, $request->user());

        return response()->json([
            'transaccion' => $transaccion->fresh(['cuentaOrigen.banco', 'cuentaDestino.banco', 'moneda']),
        ]);
    }

    /**
     * Valida una transacción (flujo legacy de verificación).
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
            'transaccion'     => $transaccion->fresh(['cuentaOrigen.banco', 'cuentaDestino.banco', 'moneda']),
            'todas_validadas' => $todasValidadas,
        ]);
    }

    /**
     * Revuelve una transacción confirmada.
     */
    public function revertir(Request $request, Operacion $operacion, Transaccion $transaccion): JsonResponse
    {
        $this->authorize('update', $operacion);

        if ($transaccion->operacion_id !== $operacion->id) {
            return response()->json(['message' => 'La transacción no pertenece a esta operación.'], 404);
        }

        if ($transaccion->estado !== 'confirmada') {
            return response()->json([
                'message' => 'Solo se pueden revertir transacciones en estado confirmada.',
            ], 422);
        }

        $request->validate([
            'motivo' => 'nullable|string|max:255',
        ]);

        $transaccion = $this->transaccionService->revertirTransaccion(
            $transaccion,
            $request->user(),
            $request->input('motivo'),
        );

        return response()->json([
            'transaccion' => $transaccion->fresh(['cuentaOrigen.banco', 'cuentaDestino.banco', 'moneda']),
        ]);
    }

    /**
     * Elimina una transacción pendiente.
     */
    public function destroy(Request $request, Operacion $operacion, Transaccion $transaccion): JsonResponse
    {
        $this->authorize('update', $operacion);

        if (! $this->estaEnFlujoActivo($operacion)) {
            return response()->json([
                'message' => 'La operación no está en un estado que permita eliminar transacciones.',
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

        activity('transacciones')
            ->performedOn($transaccion)
            ->causedBy($request->user())
            ->withProperties(['operacion_id' => $operacion->id])
            ->event('transaccion_eliminada')
            ->log('Transacción eliminada');

        return response()->json(null, 204);
    }

    /**
     * Determina si la operación está en un flujo que permite gestionar transacciones.
     * Soporta ambos: verificación legacy (estatus) y multi-paso (estado).
     */
    private function estaEnFlujoActivo(Operacion $operacion): bool
    {
        return in_array($operacion->estado, ['solicitud', 'en_progreso'])
            || $operacion->estatus === 'en_verificacion';
    }
}
