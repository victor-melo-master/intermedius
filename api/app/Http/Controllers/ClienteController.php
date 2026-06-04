<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cliente\StoreClienteRequest;
use App\Http\Requests\Cliente\UpdateClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Cliente::class);

        $clientes = Cliente::query()
            ->when($request->filled('q'), fn ($q) => $q->whereFullText(['nombre', 'alias'], $request->q))
            ->when($request->boolean('inactivos'), fn ($q) => $q->where('activo', false), fn ($q) => $q->where('activo', true))
            ->orderBy('nombre')
            ->paginate(50);

        return response()->json($clientes);
    }

    public function store(StoreClienteRequest $request): JsonResponse
    {
        $this->authorize('create', Cliente::class);

        $cliente = Cliente::create($request->validated());

        return response()->json($cliente, 201);
    }

    public function show(Cliente $cliente): JsonResponse
    {
        $this->authorize('view', $cliente);

        return response()->json($cliente->load(['cuentas.banco', 'cuentas.moneda']));
    }

    public function cuentas(Cliente $cliente): JsonResponse
    {
        $this->authorize('view', $cliente);

        return response()->json(
            $cliente->cuentas()->with(['banco', 'moneda'])->orderBy('alias')->get()
        );
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente): JsonResponse
    {
        $cliente->update($request->validated());

        return response()->json($cliente->fresh());
    }

    public function destroy(Cliente $cliente): JsonResponse
    {
        $this->authorize('delete', $cliente);

        $cliente->delete();

        return response()->json(null, 204);
    }
}
