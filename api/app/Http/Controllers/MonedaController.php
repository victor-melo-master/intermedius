<?php

namespace App\Http\Controllers;

use App\Http\Requests\Moneda\StoreMonedaRequest;
use App\Http\Requests\Moneda\UpdateMonedaRequest;
use App\Models\Moneda;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de monedas.
 */
class MonedaController extends Controller
{
    /**
     * Lista todas las monedas ordenadas por código.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Moneda::class);

        return response()->json(Moneda::orderBy('codigo')->get());
    }

    /**
     * Crea una nueva moneda.
     *
     * @param StoreMonedaRequest $request
     * @return JsonResponse
     */
    public function store(StoreMonedaRequest $request): JsonResponse
    {
        $moneda = Moneda::create($request->validated());

        return response()->json($moneda, 201);
    }

    /**
     * Muestra una moneda específica.
     *
     * @param Moneda $moneda
     * @return JsonResponse
     */
    public function show(Moneda $moneda): JsonResponse
    {
        $this->authorize('view', $moneda);

        return response()->json($moneda);
    }

    /**
     * Actualiza una moneda existente.
     *
     * @param UpdateMonedaRequest $request
     * @param Moneda $moneda
     * @return JsonResponse
     */
    public function update(UpdateMonedaRequest $request, Moneda $moneda): JsonResponse
    {
        $moneda->update($request->validated());

        return response()->json($moneda->fresh());
    }

    /**
     * Elimina una moneda.
     *
     * @param Moneda $moneda
     * @return JsonResponse
     */
    public function destroy(Moneda $moneda): JsonResponse
    {
        $this->authorize('delete', $moneda);

        $moneda->delete();

        return response()->json(null, 204);
    }
}
