<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Documento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentoController extends Controller
{
    public function index(Cliente $cliente): JsonResponse
    {
        $documentos = $cliente->documentos()->orderByDesc('created_at')->get();
        return response()->json($documentos);
    }

    public function store(Request $request, Cliente $cliente): JsonResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'max:5120'],
            'tipo'    => ['required', 'in:cedula,rif,otro'],
        ]);

        $archivo = $request->file('archivo');
        $clienteFolder = $cliente->id;
        $nombreOriginal = $archivo->getClientOriginalName();
        $extension = $archivo->getClientOriginalExtension();
        $ruta = "clientes/{$clienteFolder}/{$request->tipo}_" . time() . ".{$extension}";

        Storage::disk('s3')->put($ruta, file_get_contents($archivo));

        $documento = Documento::create([
            'cliente_id'     => $cliente->id,
            'nombre_archivo' => $nombreOriginal,
            'ruta'           => $ruta,
            'tipo'           => $request->tipo,
            'mime_type'      => $archivo->getMimeType(),
            'tamano'         => $archivo->getSize(),
            'subido_por_id'  => $request->user()->id,
        ]);

        return response()->json($documento, 201);
    }

    public function destroy(Documento $documento): JsonResponse
    {
        Storage::disk('s3')->delete($documento->ruta);
        $documento->delete();
        return response()->json(null, 204);
    }
}
