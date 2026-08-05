<?php

namespace App\Http\Controllers\Api\V1\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Ajuste;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de ajustes generales de la aplicación.
 * Permite leer y actualizar opciones clave→valor (p. ej. 'password_segura').
 */
class AjusteController extends Controller
{
    /**
     * Lista todos los ajustes de la aplicación.
     */
    public function index(): JsonResponse
    {
        $ajustes = Ajuste::orderBy('clave')->get()->map(fn (Ajuste $a) => [
            'clave'       => $a->clave,
            'valor'       => $a->valor,
            'descripcion' => $a->descripcion,
        ]);

        return response()->json($ajustes);
    }

    /**
     * Actualiza uno o varios ajustes (solo admin/super_admin).
     *
     * @param Request $request Body: { ajustes: [{ clave, valor }] } o { ajustes: { clave: valor } }
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ajustes'   => ['required', 'array'],
            'ajustes.*.clave' => ['sometimes', 'required', 'string', 'max:100'],
            'ajustes.*.valor' => ['sometimes', 'nullable'],
        ]);

        $normalizados = [];

        foreach ($validated['ajustes'] as $item) {
            if (is_array($item) && isset($item['clave'])) {
                $clave = $item['clave'];
                $valor = $item['valor'] ?? null;
            } else {
                $clave = $item;
                $valor = $validated['ajustes'][$item];
            }

            if (is_bool($valor)) {
                $valor = $valor ? '1' : '0';
            } elseif (is_null($valor)) {
                $valor = '';
            } else {
                $valor = (string) $valor;
            }

            Ajuste::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
            $normalizados[$clave] = $valor;
        }

        return response()->json(['ajustes' => $normalizados]);
    }
}
