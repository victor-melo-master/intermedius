<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cuenta\StoreCuentaRequest;
use App\Http\Requests\Cuenta\UpdateCuentaRequest;
use App\Models\Cuenta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de cuentas bancarias.
 */
class CuentaController extends Controller
{
    /**
     * Lista las cuentas con filtros opcionales por titular, cliente y moneda.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Cuenta::class);

        $cuentas = Cuenta::with(['titular', 'cliente', 'banco', 'moneda'])
            ->when($request->filled('titular_id'), fn ($q) => $q->where('titular_id', $request->titular_id))
            ->when($request->filled('cliente_id'), fn ($q) => $q->where('cliente_id', $request->cliente_id))
            ->when($request->filled('moneda_id'),  fn ($q) => $q->where('moneda_id', $request->moneda_id))
            ->orderBy('alias')
            ->get();

        return response()->json($cuentas);
    }

    /**
     * Crea una nueva cuenta bancaria.
     *
     * @param StoreCuentaRequest $request
     * @return JsonResponse
     */
    public function store(StoreCuentaRequest $request): JsonResponse
    {
        $cuenta = Cuenta::create($request->validated());

        return response()->json($cuenta->load(['titular', 'cliente', 'banco', 'moneda']), 201);
    }

    /**
     * Muestra una cuenta con sus relaciones.
     *
     * @param Cuenta $cuenta
     * @return JsonResponse
     */
    public function show(Cuenta $cuenta): JsonResponse
    {
        $this->authorize('view', $cuenta);

        return response()->json($cuenta->load(['titular', 'cliente', 'banco', 'moneda']));
    }

    /**
     * Actualiza una cuenta existente.
     *
     * @param UpdateCuentaRequest $request
     * @param Cuenta $cuenta
     * @return JsonResponse
     */
    public function update(UpdateCuentaRequest $request, Cuenta $cuenta): JsonResponse
    {
        $cuenta->update($request->validated());

        return response()->json($cuenta->fresh()->load(['titular', 'cliente', 'banco', 'moneda']));
    }

    /**
     * Elimina una cuenta bancaria.
     *
     * @param Cuenta $cuenta
     * @return JsonResponse
     */
    public function destroy(Cuenta $cuenta): JsonResponse
    {
        $this->authorize('delete', $cuenta);

        $cuenta->delete();

        return response()->json(null, 204);
    }

    /**
     * Actualiza el saldo en caché de una cuenta.
     *
     * @param Request $request
     * @param Cuenta $cuenta
     * @return JsonResponse
     */
    public function cargarSaldo(Request $request, Cuenta $cuenta): JsonResponse
    {
        $this->authorize('update', $cuenta);

        $request->validate([
            'saldo' => ['required', 'numeric', 'min:0'],
        ]);

        $cuenta->update([
            'saldo_cache'    => round((float) $request->saldo, 2),
            'saldo_cache_at' => now(),
        ]);

        return response()->json($cuenta->fresh(['banco', 'moneda']));
    }
}
