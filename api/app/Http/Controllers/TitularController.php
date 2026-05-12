<?php

namespace App\Http\Controllers;

use App\Http\Requests\Titular\StoreTitularRequest;
use App\Http\Requests\Titular\UpdateTitularRequest;
use App\Models\Titular;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TitularController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Titular::class);

        $titulares = Titular::query()
            ->when($request->boolean('inactivos'), fn ($q) => $q->where('activo', false), fn ($q) => $q->where('activo', true))
            ->orderBy('nombre')
            ->get();

        return response()->json($titulares);
    }

    public function store(StoreTitularRequest $request): JsonResponse
    {
        $titular = Titular::create($request->validated());

        return response()->json($titular, 201);
    }

    public function show(Titular $titular): JsonResponse
    {
        $this->authorize('view', $titular);

        return response()->json($titular->load('cuentas'));
    }

    public function update(UpdateTitularRequest $request, Titular $titular): JsonResponse
    {
        $titular->update($request->validated());

        return response()->json($titular->fresh());
    }

    public function destroy(Titular $titular): JsonResponse
    {
        $this->authorize('delete', $titular);

        $titular->delete();

        return response()->json(null, 204);
    }
}
