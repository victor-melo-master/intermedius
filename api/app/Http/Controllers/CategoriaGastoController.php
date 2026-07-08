<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoriaGasto\StoreCategoriaGastoRequest;
use App\Http\Requests\CategoriaGasto\UpdateCategoriaGastoRequest;
use App\Models\CategoriaGasto;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de categorías de gasto.
 */
class CategoriaGastoController extends Controller
{
    /**
     * Lista todas las categorías de gasto con su titular.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', CategoriaGasto::class);

        return response()->json(CategoriaGasto::with('titular')->orderBy('nombre')->get());
    }

    /**
     * Crea una nueva categoría de gasto.
     *
     * @param StoreCategoriaGastoRequest $request
     * @return JsonResponse
     */
    public function store(StoreCategoriaGastoRequest $request): JsonResponse
    {
        $categoria = CategoriaGasto::create($request->validated());

        return response()->json($categoria->load('titular'), 201);
    }

    /**
     * Muestra una categoría de gasto con su titular.
     *
     * @param CategoriaGasto $categoriaGasto
     * @return JsonResponse
     */
    public function show(CategoriaGasto $categoriaGasto): JsonResponse
    {
        $this->authorize('view', $categoriaGasto);

        return response()->json($categoriaGasto->load('titular'));
    }

    /**
     * Actualiza una categoría de gasto existente.
     *
     * @param UpdateCategoriaGastoRequest $request
     * @param CategoriaGasto $categoriaGasto
     * @return JsonResponse
     */
    public function update(UpdateCategoriaGastoRequest $request, CategoriaGasto $categoriaGasto): JsonResponse
    {
        $categoriaGasto->update($request->validated());

        return response()->json($categoriaGasto->fresh()->load('titular'));
    }

    /**
     * Elimina una categoría de gasto.
     *
     * @param CategoriaGasto $categoriaGasto
     * @return JsonResponse
     */
    public function destroy(CategoriaGasto $categoriaGasto): JsonResponse
    {
        $this->authorize('delete', $categoriaGasto);

        $categoriaGasto->delete();

        return response()->json(null, 204);
    }
}
