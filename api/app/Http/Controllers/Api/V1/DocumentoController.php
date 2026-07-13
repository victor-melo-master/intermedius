<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Documento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

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

        Storage::disk('s3')->put($ruta, $archivo->get());

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

    public function download(Request $request, Documento $documento)
    {
        $token = $request->query('token');
        if (!$token || !PersonalAccessToken::findToken($token)) {
            abort(401);
        }

        if (!Storage::disk('s3')->exists($documento->ruta)) {
            abort(404, 'Archivo no encontrado.');
        }

        $file = Storage::disk('s3')->get($documento->ruta);

        return response($file, 200)
            ->header('Content-Type', $documento->mime_type ?? 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename="' . $documento->nombre_archivo . '"');
    }

    /**
     * Devuelve el contenido del documento para previsualización.
     */
    public function preview(Request $request, Documento $documento)
    {
        $token = $request->query('token');
        if (!$token || !PersonalAccessToken::findToken($token)) {
            abort(401);
        }

        if (!Storage::disk('s3')->exists($documento->ruta)) {
            abort(404, 'Archivo no encontrado.');
        }

        $file = Storage::disk('s3')->get($documento->ruta);

        return response($file, 200)->header('Content-Type', $documento->mime_type ?? 'application/octet-stream');
    }
}
