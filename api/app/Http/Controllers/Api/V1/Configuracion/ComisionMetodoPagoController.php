<?php

namespace App\Http\Controllers\Api\V1\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\StoreComisionMetodoPagoRequest;
use App\Models\ComisionMetodoPago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Controlador de comisiones por método de pago.
 * CRUD de configuraciones de comisiones asociadas a métodos de pago.
 */
class ComisionMetodoPagoController extends Controller
{
    /**
     * Lista paginada de comisiones por método de pago.
     *
     * @param Request $request Filtro opcional: activa
     * @return JsonResponse Lista paginada de comisiones
     */
    public function index(Request $request): JsonResponse
    {
        $query = ComisionMetodoPago::with(['cuenta', 'moneda'])
            ->orderByDesc('created_at');

        if ($request->filled('activa')) {
            $query->where('activa', (bool) $request->input('activa'));
        }

        return response()->json(['data' => $query->paginate(50)]);
    }

    /**
     * Crea una nueva comisión por método de pago.
     *
     * @param StoreComisionMetodoPagoRequest $request Datos validados de la comisión
     * @return JsonResponse Comisión creada con código 201
     */
    public function store(StoreComisionMetodoPagoRequest $request): JsonResponse
    {
        $comision = ComisionMetodoPago::create($request->validated());
        $comision->load(['cuenta', 'moneda']);

        return response()->json(['data' => $comision], 201);
    }

    /**
     * Muestra los detalles de una comisión por método de pago.
     *
     * @param ComisionMetodoPago $comisionMetodoPago Comisión a consultar
     * @return JsonResponse Datos de la comisión con relaciones
     */
    public function show(ComisionMetodoPago $comisionMetodoPago): JsonResponse
    {
        return response()->json(['data' => $comisionMetodoPago->load(['cuenta', 'moneda'])]);
    }

    /**
     * Actualiza una comisión por método de pago.
     *
     * @param StoreComisionMetodoPagoRequest $request Datos validados de actualización
     * @param ComisionMetodoPago $comisionMetodoPago Comisión a modificar
     * @return JsonResponse Comisión actualizada
     */
    public function update(StoreComisionMetodoPagoRequest $request, ComisionMetodoPago $comisionMetodoPago): JsonResponse
    {
        $comisionMetodoPago->update($request->validated());

        return response()->json(['data' => $comisionMetodoPago->fresh(['cuenta', 'moneda'])]);
    }

    /**
     * Desactiva (borrado lógico) una comisión por método de pago.
     *
     * @param ComisionMetodoPago $comisionMetodoPago Comisión a desactivar
     * @return JsonResponse Comisión desactivada con vigencia terminada
     */
    public function destroy(ComisionMetodoPago $comisionMetodoPago): JsonResponse
    {
        $comisionMetodoPago->update([
            'activa'        => false,
            'vigente_hasta' => Carbon::today()->toDateString(),
        ]);

        return response()->json(['data' => $comisionMetodoPago->fresh()]);
    }
}
