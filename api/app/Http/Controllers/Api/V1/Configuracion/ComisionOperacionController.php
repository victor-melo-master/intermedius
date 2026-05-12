<?php

namespace App\Http\Controllers\Api\V1\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\UpdateComisionOperacionRequest;
use App\Models\ComisionOperacion;
use App\Models\Operacion;
use App\Services\Configuracion\CalculadorComisionesService;
use Illuminate\Http\JsonResponse;

class ComisionOperacionController extends Controller
{
    public function __construct(private readonly CalculadorComisionesService $service) {}

    public function index(Operacion $operacion): JsonResponse
    {
        $comisiones = ComisionOperacion::where('operacion_id', $operacion->id)
            ->with(['moneda', 'origen', 'movimiento', 'editadaPor'])
            ->orderBy('tipo')
            ->get();

        return response()->json(['data' => $comisiones]);
    }

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
