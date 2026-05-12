<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoriaGasto\StoreCategoriaGastoRequest;
use App\Http\Requests\CategoriaGasto\UpdateCategoriaGastoRequest;
use App\Models\CategoriaGasto;
use Illuminate\Http\JsonResponse;

class CategoriaGastoController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', CategoriaGasto::class);

        return response()->json(CategoriaGasto::with('titular')->orderBy('nombre')->get());
    }

    public function store(StoreCategoriaGastoRequest $request): JsonResponse
    {
        $categoria = CategoriaGasto::create($request->validated());

        return response()->json($categoria->load('titular'), 201);
    }

    public function show(CategoriaGasto $categoriaGasto): JsonResponse
    {
        $this->authorize('view', $categoriaGasto);

        return response()->json($categoriaGasto->load('titular'));
    }

    public function update(UpdateCategoriaGastoRequest $request, CategoriaGasto $categoriaGasto): JsonResponse
    {
        $categoriaGasto->update($request->validated());

        return response()->json($categoriaGasto->fresh()->load('titular'));
    }

    public function destroy(CategoriaGasto $categoriaGasto): JsonResponse
    {
        $this->authorize('delete', $categoriaGasto);

        $categoriaGasto->delete();

        return response()->json(null, 204);
    }
}
