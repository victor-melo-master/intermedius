<?php

namespace App\Http\Controllers;

use App\Http\Requests\Moneda\StoreMonedaRequest;
use App\Http\Requests\Moneda\UpdateMonedaRequest;
use App\Models\Moneda;
use Illuminate\Http\JsonResponse;

class MonedaController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Moneda::class);

        return response()->json(Moneda::orderBy('codigo')->get());
    }

    public function store(StoreMonedaRequest $request): JsonResponse
    {
        $moneda = Moneda::create($request->validated());

        return response()->json($moneda, 201);
    }

    public function show(Moneda $moneda): JsonResponse
    {
        $this->authorize('view', $moneda);

        return response()->json($moneda);
    }

    public function update(UpdateMonedaRequest $request, Moneda $moneda): JsonResponse
    {
        $moneda->update($request->validated());

        return response()->json($moneda->fresh());
    }

    public function destroy(Moneda $moneda): JsonResponse
    {
        $this->authorize('delete', $moneda);

        $moneda->delete();

        return response()->json(null, 204);
    }
}
