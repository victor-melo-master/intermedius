<?php

namespace App\Http\Controllers\Api\V1\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreComisionOperadorRequest;
use App\Models\ComisionOperador;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ComisionOperadorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ComisionOperador::with(['titular', 'tipoOperacion', 'moneda'])
            ->orderByDesc('created_at');

        if ($request->filled('titular_id')) {
            $query->where('titular_id', $request->integer('titular_id'));
        }
        if ($request->filled('activa')) {
            $query->where('activa', (bool) $request->input('activa'));
        }

        return response()->json(['data' => $query->paginate(50)]);
    }

    public function store(StoreComisionOperadorRequest $request): JsonResponse
    {
        $comision = ComisionOperador::create($request->validated());
        $comision->load(['titular', 'tipoOperacion', 'moneda']);

        return response()->json(['data' => $comision], 201);
    }

    public function show(ComisionOperador $comisionOperador): JsonResponse
    {
        return response()->json(['data' => $comisionOperador->load(['titular', 'tipoOperacion', 'moneda'])]);
    }

    public function update(StoreComisionOperadorRequest $request, ComisionOperador $comisionOperador): JsonResponse
    {
        $comisionOperador->update($request->validated());

        return response()->json(['data' => $comisionOperador->fresh(['titular', 'tipoOperacion', 'moneda'])]);
    }

    public function destroy(ComisionOperador $comisionOperador): JsonResponse
    {
        $comisionOperador->update([
            'activa'        => false,
            'vigente_hasta' => Carbon::today()->toDateString(),
        ]);

        return response()->json(['data' => $comisionOperador->fresh()]);
    }
}
