<?php

namespace App\Http\Controllers;

use App\Http\Requests\Titular\StoreTitularRequest;
use App\Http\Requests\Titular\UpdateTitularRequest;
use App\Models\Titular;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de titulares.
 */
class TitularController extends Controller
{
    /**
     * Lista titulares con filtro opcional de activos/inactivos.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Titular::class);

        $titulares = Titular::query()
            ->when($request->boolean('inactivos'), fn ($q) => $q->where('activo', false), fn ($q) => $q->where('activo', true))
            ->orderBy('nombre')
            ->get();

        return response()->json($titulares);
    }

    /**
     * Crea un nuevo titular.
     *
     * @param StoreTitularRequest $request
     * @return JsonResponse
     */
    public function store(StoreTitularRequest $request): JsonResponse
    {
        $titular = Titular::create($request->validated());

        return response()->json($titular, 201);
    }

    /**
     * Muestra un titular con sus cuentas.
     *
     * @param Titular $titular
     * @return JsonResponse
     */
    public function show(Titular $titular): JsonResponse
    {
        $this->authorize('view', $titular);

        return response()->json($titular->load('cuentas'));
    }

    /**
     * Actualiza un titular existente.
     *
     * @param UpdateTitularRequest $request
     * @param Titular $titular
     * @return JsonResponse
     */
    public function update(UpdateTitularRequest $request, Titular $titular): JsonResponse
    {
        $titular->update($request->validated());

        return response()->json($titular->fresh());
    }

    /**
     * Elimina un titular.
     *
     * @param Titular $titular
     * @return JsonResponse
     */
    public function destroy(Titular $titular): JsonResponse
    {
        $this->authorize('delete', $titular);

        $titular->delete();

        return response()->json(null, 204);
    }
}
