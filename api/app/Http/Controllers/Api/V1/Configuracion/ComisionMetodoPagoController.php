<?php

namespace App\Http\Controllers\Api\V1\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreComisionMetodoPagoRequest;
use App\Models\ComisionMetodoPago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ComisionMetodoPagoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ComisionMetodoPago::with(['cuenta', 'moneda'])
            ->orderByDesc('created_at');

        if ($request->filled('activa')) {
            $query->where('activa', (bool) $request->input('activa'));
        }

        return response()->json(['data' => $query->paginate(50)]);
    }

    public function store(StoreComisionMetodoPagoRequest $request): JsonResponse
    {
        $comision = ComisionMetodoPago::create($request->validated());
        $comision->load(['cuenta', 'moneda']);

        return response()->json(['data' => $comision], 201);
    }

    public function show(ComisionMetodoPago $comisionMetodoPago): JsonResponse
    {
        return response()->json(['data' => $comisionMetodoPago->load(['cuenta', 'moneda'])]);
    }

    public function update(StoreComisionMetodoPagoRequest $request, ComisionMetodoPago $comisionMetodoPago): JsonResponse
    {
        $comisionMetodoPago->update($request->validated());

        return response()->json(['data' => $comisionMetodoPago->fresh(['cuenta', 'moneda'])]);
    }

    public function destroy(ComisionMetodoPago $comisionMetodoPago): JsonResponse
    {
        $comisionMetodoPago->update([
            'activa'        => false,
            'vigente_hasta' => Carbon::today()->toDateString(),
        ]);

        return response()->json(['data' => $comisionMetodoPago->fresh()]);
    }
}
