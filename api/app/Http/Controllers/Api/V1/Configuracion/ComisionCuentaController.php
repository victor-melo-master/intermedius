<?php

namespace App\Http\Controllers\Api\V1\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreComisionCuentaRequest;
use App\Models\ComisionCuenta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ComisionCuentaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ComisionCuenta::with(['cuenta', 'banco', 'moneda'])
            ->orderByDesc('created_at');

        if ($request->filled('activa')) {
            $query->where('activa', (bool) $request->input('activa'));
        }

        return response()->json(['data' => $query->paginate(50)]);
    }

    public function store(StoreComisionCuentaRequest $request): JsonResponse
    {
        $comision = ComisionCuenta::create($request->validated());
        $comision->load(['cuenta', 'banco', 'moneda']);

        return response()->json(['data' => $comision], 201);
    }

    public function show(ComisionCuenta $comisionCuenta): JsonResponse
    {
        return response()->json(['data' => $comisionCuenta->load(['cuenta', 'banco', 'moneda'])]);
    }

    public function update(StoreComisionCuentaRequest $request, ComisionCuenta $comisionCuenta): JsonResponse
    {
        $comisionCuenta->update($request->validated());

        return response()->json(['data' => $comisionCuenta->fresh(['cuenta', 'banco', 'moneda'])]);
    }

    public function destroy(ComisionCuenta $comisionCuenta): JsonResponse
    {
        $comisionCuenta->update([
            'activa'        => false,
            'vigente_hasta' => Carbon::today()->toDateString(),
        ]);

        return response()->json(['data' => $comisionCuenta->fresh()]);
    }
}
