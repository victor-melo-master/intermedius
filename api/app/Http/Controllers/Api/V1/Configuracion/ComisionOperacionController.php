<?php

namespace App\Http\Controllers\Api\V1\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\UpdateComisionOperacionRequest;
use App\Models\ComisionOperacion;
use App\Models\Operacion;
use App\Services\Configuracion\CalculadorComisionesService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de comisiones por operación.
 * Consulta y edición de comisiones aplicadas a una operación específica.
 */
class ComisionOperacionController extends Controller
{
    public function __construct(private readonly CalculadorComisionesService $service) {}

    /**
     * Lista las comisiones aplicadas a una operación.
     *
     * @param Operacion $operacion Operación de la cual listar comisiones
     * @return JsonResponse Lista de comisiones con relaciones
     */
    public function index(Operacion $operacion): JsonResponse
    {
        $comisiones = ComisionOperacion::where('operacion_id', $operacion->id)
            ->with(['moneda', 'origen', 'movimiento', 'editadaPor'])
            ->orderBy('tipo')
            ->get();

        return response()->json(['data' => $comisiones]);
    }

    /**
     * Edita una comisión específica de una operación.
     *
     * @param UpdateComisionOperacionRequest $request Datos de edición (monto, monto_usd_equivalente, descripcion, razon_edicion)
     * @param Operacion $operacion Operación a la que pertenece la comisión
     * @param ComisionOperacion $comision Comisión a editar
     * @return JsonResponse Comisión actualizada con datos frescos
     */
    public function update(
        UpdateComisionOperacionRequest $request,
        Operacion $operacion,
        ComisionOperacion $comision
    ): JsonResponse {
        abort_if($comision->operacion_id !== $operacion->id, 404);

        $this->service->editarComision(
            $comision,
            $request->only(['monto', 'monto_usd_equivalente', 'descripcion']),
            $request->user(),
            $request->input('razon_edicion'),
        );

        return response()->json(['data' => $comision->fresh(['moneda', 'editadaPor'])]);
    }
}
