<?php

namespace App\Http\Controllers\Api\V1\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreComisionCuentaRequest;
use App\Models\ComisionCuenta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Controlador de comisiones por cuenta bancaria.
 * CRUD de configuraciones de comisiones asociadas a cuentas.
 */
class ComisionCuentaController extends Controller
{
    /**
     * Lista paginada de comisiones por cuenta.
     *
     * @param Request $request Filtro opcional: activa
     * @return JsonResponse Lista paginada de comisiones
     */
    public function index(Request $request): JsonResponse
    {
        $query = ComisionCuenta::with(['cuenta', 'banco', 'moneda'])
            ->orderByDesc('created_at');

        if ($request->filled('activa')) {
            $query->where('activa', (bool) $request->input('activa'));
        }

        return response()->json(['data' => $query->paginate(50)]);
    }

    /**
     * Crea una nueva comisión por cuenta.
     *
     * @param StoreComisionCuentaRequest $request Datos validados de la comisión
     * @return JsonResponse Comisión creada con código 201
     */
    public function store(StoreComisionCuentaRequest $request): JsonResponse
    {
        $comision = ComisionCuenta::create($request->validated());
        $comision->load(['cuenta', 'banco', 'moneda']);

        return response()->json(['data' => $comision], 201);
    }

    /**
     * Muestra los detalles de una comisión por cuenta.
     *
     * @param ComisionCuenta $comisionCuenta Comisión a consultar
     * @return JsonResponse Datos de la comisión con relaciones
     */
    public function show(ComisionCuenta $comisionCuenta): JsonResponse
    {
        return response()->json(['data' => $comisionCuenta->load(['cuenta', 'banco', 'moneda'])]);
    }

    /**
     * Actualiza una comisión por cuenta.
     *
     * @param StoreComisionCuentaRequest $request Datos validados de actualización
     * @param ComisionCuenta $comisionCuenta Comisión a modificar
     * @return JsonResponse Comisión actualizada
     */
    public function update(StoreComisionCuentaRequest $request, ComisionCuenta $comisionCuenta): JsonResponse
    {
        $comisionCuenta->update($request->validated());

        return response()->json(['data' => $comisionCuenta->fresh(['cuenta', 'banco', 'moneda'])]);
    }

    /**
     * Desactiva (borrado lógico) una comisión por cuenta.
     *
     * @param ComisionCuenta $comisionCuenta Comisión a desactivar
     * @return JsonResponse Comisión desactivada con vigencia terminada
     */
    public function destroy(ComisionCuenta $comisionCuenta): JsonResponse
    {
        $comisionCuenta->update([
            'activa'        => false,
            'vigente_hasta' => Carbon::today()->toDateString(),
        ]);

        return response()->json(['data' => $comisionCuenta->fresh()]);
    }
}
