<?php

namespace App\Http\Controllers;

use App\Http\Requests\Banco\StoreBancoRequest;
use App\Http\Requests\Banco\UpdateBancoRequest;
use App\Models\Banco;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de bancos.
 */
class BancoController extends Controller
{
    /**
     * Lista todos los bancos ordenados por nombre.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Banco::class);

        return response()->json(Banco::orderBy('nombre')->get());
    }

    /**
     * Crea un nuevo banco.
     *
     * @param StoreBancoRequest $request
     * @return JsonResponse
     */
    public function store(StoreBancoRequest $request): JsonResponse
    {
        $banco = Banco::create($request->validated());

        return response()->json($banco, 201);
    }

    /**
     * Muestra un banco específico.
     *
     * @param Banco $banco
     * @return JsonResponse
     */
    public function show(Banco $banco): JsonResponse
    {
        $this->authorize('view', $banco);

        return response()->json($banco);
    }

    /**
     * Actualiza un banco existente.
     *
     * @param UpdateBancoRequest $request
     * @param Banco $banco
     * @return JsonResponse
     */
    public function update(UpdateBancoRequest $request, Banco $banco): JsonResponse
    {
        $banco->update($request->validated());

        return response()->json($banco->fresh());
    }

    /**
     * Elimina un banco.
     *
     * @param Banco $banco
     * @return JsonResponse
     */
    public function destroy(Banco $banco): JsonResponse
    {
        $this->authorize('delete', $banco);

        $banco->delete();

        return response()->json(null, 204);
    }
}
