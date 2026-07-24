<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cliente\StoreClienteRequest;
use App\Http\Requests\Cliente\UpdateClienteRequest;
use App\Models\Cliente;
use App\Models\Operacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de clientes.
 */
class ClienteController extends Controller
{
    /**
     * Lista los clientes con filtros opcionales de búsqueda e inactivos.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Cliente::class);

        $clientes = Cliente::query()
            ->when($request->boolean('inactivos'), fn($q) => $q->onlyTrashed())
            ->when($request->filled('q'), function ($q) use ($request) {
                $search = $request->q;
                $q->where(function ($sub) use ($search) {
                    $sub->where('nombre', 'like', "%{$search}%")
                        ->orWhere('alias', 'like', "%{$search}%");
                });
            })
            ->orderBy('nombre')
            ->paginate(50);

        return response()->json($clientes);
    }

    /**
     * Crea un nuevo cliente.
     *
     * @param StoreClienteRequest $request
     * @return JsonResponse
     */
    public function store(StoreClienteRequest $request): JsonResponse
    {
        $this->authorize('create', Cliente::class);

        $cliente = Cliente::create($request->validated());

        return response()->json($cliente, 201);
    }

    /**
     * Muestra un cliente con sus cuentas relacionadas.
     *
     * @param Cliente $cliente
     * @return JsonResponse
     */
    public function show(Cliente $cliente): JsonResponse
    {
        $this->authorize('view', $cliente);

        return response()->json($cliente->load(['cuentas.banco', 'cuentas.moneda']));
    }

    /**
     * Devuelve las cuentas de un cliente ordenadas por alias.
     *
     * @param Cliente $cliente
     * @return JsonResponse
     */
    public function cuentas(Cliente $cliente): JsonResponse
    {
        $this->authorize('view', $cliente);

        return response()->json(
            $cliente->cuentas()->with(['banco', 'moneda'])->orderBy('alias')->get()
        );
    }

    /**
     * Actualiza un cliente existente.
     *
     * @param UpdateClienteRequest $request
     * @param Cliente $cliente
     * @return JsonResponse
     */
    public function update(UpdateClienteRequest $request, Cliente $cliente): JsonResponse
    {
        $cliente->update($request->validated());

        return response()->json($cliente->fresh());
    }

    /**
     * Elimina un cliente (soft delete).
     *
     * @param Cliente $cliente
     * @return JsonResponse
     */
    public function destroy(Cliente $cliente): JsonResponse
    {
        $this->authorize('delete', $cliente);

        $cliente->delete();

        return response()->json(null, 204);
    }

    /**
     * Lista las operaciones de un cliente con filtros opcionales de fecha y tipo.
     *
     * @param Request $request
     * @param Cliente $cliente
     * @return JsonResponse
     */
    public function operaciones(Request $request, Cliente $cliente): JsonResponse
    {
        $this->authorize('view', $cliente);

        $query = Operacion::with(['tipoOperacion', 'movimientos.moneda'])
            ->where('cliente_id', $cliente->id)
            ->when($request->filled('fecha_desde'), fn($q) => $q->where('fecha', '>=', $request->fecha_desde))
            ->when($request->filled('fecha_hasta'), fn($q) => $q->where('fecha', '<=', $request->fecha_hasta))
            ->when($request->filled('tipo_codigo'), fn($q) => $q->whereHas('tipoOperacion', fn($t) => $t->where('codigo', $request->tipo_codigo)))
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        $operaciones = $query->paginate(20);

        return response()->json($operaciones);
    }

    /**
     * Exporta las operaciones de un cliente en un archivo PDF.
     *
     * @param Request $request
     * @param Cliente $cliente
     * @return \Illuminate\Http\Response
     */
    public function exportarOperaciones(Request $request, Cliente $cliente)
    {
        $this->authorize('view', $cliente);

        $query = Operacion::with(['tipoOperacion', 'movimientos.moneda'])
            ->where('cliente_id', $cliente->id)
            ->when($request->filled('fecha_desde'), fn($q) => $q->where('fecha', '>=', $request->fecha_desde))
            ->when($request->filled('fecha_hasta'), fn($q) => $q->where('fecha', '<=', $request->fecha_hasta))
            ->when($request->filled('tipo_codigo'), fn($q) => $q->whereHas('tipoOperacion', fn($t) => $t->where('codigo', $request->tipo_codigo)))
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        $operaciones = $query->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reportes.cliente_operaciones', [
            'cliente' => $cliente,
            'operaciones' => $operaciones,
            'filtros' => [
                'fecha_desde' => $request->input('fecha_desde', ''),
                'fecha_hasta' => $request->input('fecha_hasta', ''),
                'tipo_codigo' => $request->input('tipo_codigo', ''),
            ],
        ]);

        return response($pdf->output(), 200)
    ->header('Content-Type', 'application/pdf')
    ->header('Content-Disposition', 'attachment; filename="operaciones_'.$cliente->nombre.'.pdf"');
    }

    /**
     * Devuelve los registros de pago de un cliente.
     *
     * @param Cliente $cliente
     * @return \Illuminate\Http\JsonResponse
     */
    public function registrosPago(Cliente $cliente): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $cliente);

        return response()->json(
            $cliente->registrosPago()->get()
        );
    }

    /**
     * Restaura un cliente eliminado (soft delete).
     *
     * @param Cliente $cliente
     * @return JsonResponse
     */
    public function restaurar(Cliente $cliente): JsonResponse
    {
        $this->authorize('restore', $cliente);

        if (!$cliente->trashed()) {
            return response()->json(['message' => 'El cliente no está eliminado.'], 422);
        }

        $cliente->restore();

        return response()->json([
            'message' => 'Cliente restaurado correctamente.',
            'cliente' => $cliente,
        ]);
    }
}
