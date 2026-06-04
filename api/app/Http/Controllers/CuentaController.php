<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cuenta\StoreCuentaRequest;
use App\Http\Requests\Cuenta\UpdateCuentaRequest;
use App\Models\Cuenta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CuentaController extends Controller
{
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

    public function store(StoreCuentaRequest $request): JsonResponse
    {
        $cuenta = Cuenta::create($request->validated());

        return response()->json($cuenta->load(['titular', 'cliente', 'banco', 'moneda']), 201);
    }

    public function show(Cuenta $cuenta): JsonResponse
    {
        $this->authorize('view', $cuenta);

        return response()->json($cuenta->load(['titular', 'cliente', 'banco', 'moneda']));
    }

    public function update(UpdateCuentaRequest $request, Cuenta $cuenta): JsonResponse
    {
        $cuenta->update($request->validated());

        return response()->json($cuenta->fresh()->load(['titular', 'cliente', 'banco', 'moneda']));
    }

    public function destroy(Cuenta $cuenta): JsonResponse
    {
        $this->authorize('delete', $cuenta);

        $cuenta->delete();

        return response()->json(null, 204);
    }
}
