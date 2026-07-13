<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->string('nombre_archivo');      // Nombre original del archivo
            $table->string('ruta');                // Ruta en MinIO/S3
            $table->string('tipo', 10)->default('otro'); // 'cedula', 'rif', 'otro'
            $table->string('mime_type', 100);      // Tipo MIME (image/png, application/pdf, etc.)
            $table->unsignedBigInteger('tamano');  // Tamaño en bytes
            $table->foreignId('subido_por_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
