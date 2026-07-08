<?php

namespace App\Http\Controllers\Api\V1\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreComisionOperadorRequest;
use App\Models\ComisionOperador;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Controlador de comisiones por operador.
 * CRUD de configuraciones de comisiones asignadas a operadores.
 */
class ComisionOperadorController extends Controller
{
    /**
     * Lista paginada de comisiones por operador con filtros.
     *
     * @param Request $request Filtros opcionales: titular_id, activa
     * @return JsonResponse Lista paginada de comisiones
     */
    public function index(Request $request): JsonResponse
    {
        $query = ComisionOperador::with(['titular', 'tipoOperacion', 'moneda'])
            ->orderByDesc('created_at');

        if ($request->filled('titular_id')) {
            $query->where('titular_id', $request->integer('titular_id'));
        }
        if ($request->filled('activa')) {
            $query->where('activa', (bool) $request->input('activa'));
        }

        return response()->json(['data' => $query->paginate(50)]);
    }

    /**
     * Crea una nueva comisión por operador.
     *
     * @param StoreComisionOperadorRequest $request Datos validados de la comisión
     * @return JsonResponse Comisión creada con código 201
     */
    public function store(StoreComisionOperadorRequest $request): JsonResponse
    {
        $comision = ComisionOperador::create($request->validated());
        $comision->load(['titular', 'tipoOperacion', 'moneda']);

        return response()->json(['data' => $comision], 201);
    }

    /**
     * Muestra los detalles de una comisión por operador.
     *
     * @param ComisionOperador $comisionOperador Comisión a consultar
     * @return JsonResponse Datos de la comisión con relaciones
     */
    public function show(ComisionOperador $comisionOperador): JsonResponse
    {
        return response()->json(['data' => $comisionOperador->load(['titular', 'tipoOperacion', 'moneda'])]);
    }

    /**
     * Actualiza una comisión por operador.
     *
     * @param StoreComisionOperadorRequest $request Datos validados de actualización
     * @param ComisionOperador $comisionOperador Comisión a modificar
     * @return JsonResponse Comisión actualizada
     */
    public function update(StoreComisionOperadorRequest $request, ComisionOperador $comisionOperador): JsonResponse
    {
        $comisionOperador->update($request->validated());

        return response()->json(['data' => $comisionOperador->fresh(['titular', 'tipoOperacion', 'moneda'])]);
    }

    /**
     * Desactiva (borrado lógico) una comisión por operador.
     *
     * @param ComisionOperador $comisionOperador Comisión a desactivar
     * @return JsonResponse Comisión desactivada con vigencia terminada
     */
    public function destroy(ComisionOperador $comisionOperador): JsonResponse
    {
        $comisionOperador->update([
            'activa'        => false,
            'vigente_hasta' => Carbon::today()->toDateString(),
        ]);

        return response()->json(['data' => $comisionOperador->fresh()]);
    }
}
