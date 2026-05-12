<?php

namespace App\Http\Controllers;

use App\Http\Requests\Banco\StoreBancoRequest;
use App\Http\Requests\Banco\UpdateBancoRequest;
use App\Models\Banco;
use Illuminate\Http\JsonResponse;

class BancoController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Banco::class);

        return response()->json(Banco::orderBy('nombre')->get());
    }

    public function store(StoreBancoRequest $request): JsonResponse
    {
        $banco = Banco::create($request->validated());

        return response()->json($banco, 201);
    }

    public function show(Banco $banco): JsonResponse
    {
        $this->authorize('view', $banco);

        return response()->json($banco);
    }

    public function update(UpdateBancoRequest $request, Banco $banco): JsonResponse
    {
        $banco->update($request->validated());

        return response()->json($banco->fresh());
    }

    public function destroy(Banco $banco): JsonResponse
    {
        $this->authorize('delete', $banco);

        $banco->delete();

        return response()->json(null, 204);
    }
}
