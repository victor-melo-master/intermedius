<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AvatarService
{
    /** Tamaño máximo del avatar (px). */
    public const TAMANO = 512;

    /** Calidad WebP (0-100). */
    public const CALIDAD = 85;

    /**
     * Convierte una imagen subida a WebP, la recorta en cuadrado y la guarda
     * en el disco s3. Retorna la ruta del archivo guardado.
     *
     * Formatos aceptados: jpeg, png, gif, webp, bmp (rasterizables con GD).
     *
     * @param UploadedFile $archivo Imagen recibida del formulario
     * @param string $carpeta Carpeta dentro de avatars/ (ej: 'usuarios' o 'clientes')
     * @param int $entidadId ID de la entidad dueña del avatar
     * @param string|null $rutaPrevia Ruta anterior para borrarla al reemplazar
     * @return string Ruta s3 del avatar (ej: avatars/usuarios/5/avatar_123.webp)
     *
     * @throws RuntimeException Si la imagen no puede leerse o convertirse
     */
    public function guardar(UploadedFile $archivo, string $carpeta, int $entidadId, ?string $rutaPrevia = null): string
    {
        $contenido = $archivo->get();
        $imagen = @imagecreatefromstring($contenido);

        if ($imagen === false) {
            throw new RuntimeException('No se pudo leer la imagen. Asegúrate de que sea un archivo de imagen válido.');
        }

        try {
            $imagen = $this->corregirOrientacion($imagen, $contenido);
            $imagen = $this->recortarCuadrado($imagen);

            $ancho = imagesx($imagen);
            $alto = imagesy($imagen);
            $tamano = self::TAMANO;

            $nueva = imagecreatetruecolor($tamano, $tamano);
            imagecopyresampled($nueva, $imagen, 0, 0, 0, 0, $tamano, $tamano, $ancho, $alto);

            $buffer = $this->codificarWebp($nueva);
        } finally {
            imagedestroy($imagen);
            if (isset($nueva)) {
                imagedestroy($nueva);
            }
        }

        if ($rutaPrevia) {
            Storage::disk('s3')->delete($rutaPrevia);
        }

        $ruta = "avatars/{$carpeta}/{$entidadId}/avatar_" . time() . '.webp';
        Storage::disk('s3')->put($ruta, $buffer, ['ContentType' => 'image/webp']);

        return $ruta;
    }

    /**
     * Aplica la rotación indicada por los metadatos EXIF (fotos de celulares).
     *
     * @param \GdImage $imagen
     * @param string $contenido Bytes originales del archivo
     * @return \GdImage Imagen rotada si aplicaba orientación EXIF
     */
    private function corregirOrientacion($imagen, string $contenido)
    {
        // Solo los JPEG pueden traer orientación EXIF; evita warnings con otros formatos.
        if (!function_exists('exif_read_data') || !str_starts_with($contenido, "\xFF\xD8\xFF")) {
            return $imagen;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'avatar');
        file_put_contents($tmp, $contenido);
        $exif = @exif_read_data($tmp);
        unlink($tmp);

        $orientacion = $exif['Orientation'] ?? null;

        switch ($orientacion) {
            case 3:
                $rotada = imagerotate($imagen, 180, 0);
                break;
            case 6:
                $rotada = imagerotate($imagen, -90, 0);
                break;
            case 8:
                $rotada = imagerotate($imagen, 90, 0);
                break;
            default:
                return $imagen;
        }

        imagedestroy($imagen);
        return $rotada;
    }

    /**
     * Recorta la imagen en cuadrado, tomando el centro como referencia.
     *
     * @param \GdImage $imagen
     * @return \GdImage Imagen cuadrada
     */
    private function recortarCuadrado($imagen)
    {
        $ancho = imagesx($imagen);
        $alto = imagesy($imagen);
        $lado = min($ancho, $alto);

        $x = (int) floor(($ancho - $lado) / 2);
        $y = (int) floor(($alto - $lado) / 2);

        $cuadrada = imagecreatetruecolor($lado, $lado);
        imagecopy($cuadrada, $imagen, 0, 0, $x, $y, $lado, $lado);

        imagedestroy($imagen);
        return $cuadrada;
    }

    /**
     * Codifica una imagen GD como WebP.
     *
     * @param \GdImage $imagen
     * @return string Bytes de la imagen WebP
     *
     * @throws RuntimeException Si GD no soporta WebP o falla la codificación
     */
    private function codificarWebp($imagen): string
    {
        if (!function_exists('imagewebp')) {
            throw new RuntimeException('La extensión GD de PHP no soporta WebP en este servidor.');
        }

        ob_start();
        $ok = imagewebp($imagen, null, self::CALIDAD);
        $buffer = ob_get_clean();

        if (!$ok || $buffer === false) {
            throw new RuntimeException('No se pudo convertir la imagen a WebP.');
        }

        return $buffer;
    }
}
