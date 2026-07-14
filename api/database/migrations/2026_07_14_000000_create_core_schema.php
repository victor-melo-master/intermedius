<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monedas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10);
            $table->string('nombre');
            $table->string('simbolo', 10)->nullable();
            $table->boolean('es_fiat')->default(true);
            $table->boolean('es_cripto')->default(false);
            $table->tinyInteger('decimales')->default(2);
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
        Schema::create('titulares', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('alias')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('bancos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('codigo')->nullable();
            $table->char('pais', 2)->default('VE');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique('nombre');
        });
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('alias')->nullable();
            $table->string('documento')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->text('notas')->nullable();
            $table->decimal('saldo_cache_usd', 20, 4)->default(0);
            $table->timestamp('saldo_cache_at')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('titular_id')->nullable()->constrained('titulares');
            $table->string('name');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('remember_token', 100)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('categorias_gasto', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('titular_id')->nullable()->constrained('titulares');
            $table->boolean('activa')->default(true);
            $table->timestamps();
            $table->unique('nombre');
        });
        Schema::create('tipos_operacion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30);
            $table->string('nombre');
            $table->boolean('afecta_cliente')->default(false);
            $table->boolean('afecta_fifo')->default(false);
            $table->boolean('genera_ganancia')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('cuentas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('titular_id')->nullable()->constrained('titulares');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes');
            $table->foreignId('banco_id')->nullable()->constrained('bancos');
            $table->foreignId('moneda_id')->constrained('monedas');
            $table->string('alias');
            $table->string('tipo');
            $table->string('numero_cuenta')->nullable();
            $table->decimal('saldo_cache', 20, 4)->default(0);
            $table->timestamp('saldo_cache_at')->nullable();
            $table->boolean('activa')->default(true);
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('tasas_diarias', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->foreignId('moneda_base_id')->constrained('monedas');
            $table->foreignId('moneda_cotizada_id')->constrained('monedas');
            $table->decimal('tasa_compra', 20, 8);
            $table->decimal('tasa_compra_minima', 20, 8)->nullable();
            $table->decimal('tasa_venta', 20, 8);
            $table->decimal('tasa_venta_minima', 20, 8)->nullable();
            $table->foreignId('definida_por_id')->constrained('users');
            $table->text('notas')->nullable();
            $table->timestamp('vigente_desde')->useCurrent();
            $table->timestamp('vigente_hasta')->nullable();
            $table->timestamps();
        });
        Schema::create('tasas_mercado', function (Blueprint $table) {
            $table->id();
            $table->string('fuente', 30);
            $table->foreignId('moneda_base_id')->constrained('monedas');
            $table->foreignId('moneda_cotizada_id')->constrained('monedas');
            $table->decimal('valor', 20, 8);
            $table->timestamp('capturado_en')->useCurrent();
            $table->text('payload_original')->nullable();
            $table->timestamps();
        });
        Schema::create('operaciones', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->foreignId('tipo_operacion_id')->constrained('tipos_operacion');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes');
            $table->foreignId('cliente_emisor_id')->nullable()->constrained('clientes');
            $table->foreignId('cliente_receptor_id')->nullable()->constrained('clientes');
            $table->foreignId('categoria_gasto_id')->nullable()->constrained('categorias_gasto');
            $table->foreignId('operador_id')->constrained('users');
            $table->decimal('tasa_aplicada', 20, 8)->nullable();
            $table->decimal('tasa_compra', 20, 8)->nullable();
            $table->decimal('tasa_venta', 20, 8)->nullable();
            $table->boolean('genera_comision')->default(false);
            $table->decimal('monto_comision', 20, 4)->default(0);
            $table->string('tipo_comision', 50)->nullable();
            $table->decimal('tasa_sugerida', 20, 8)->nullable();
            $table->foreignId('tasa_diaria_id')->nullable()->constrained('tasas_diarias');
            $table->boolean('sin_tasa_referencia')->default(false);
            $table->decimal('tasa_mercado_snapshot', 20, 8)->nullable();
            $table->string('fuente_tasa_mercado', 30)->nullable();
            $table->decimal('ganancia_bruta_usd', 20, 4)->default(0);
            $table->decimal('ganancia_real_usd', 20, 4)->nullable();
            $table->decimal('ganancia_bruta_ves', 20, 2)->default(0);
            $table->decimal('ganancia_real_ves', 20, 2)->nullable();
            $table->decimal('total_comisiones_usd', 20, 4)->default(0);
            $table->decimal('total_comisiones_ves', 20, 2)->default(0);
            $table->decimal('ganancia_neta_usd', 20, 4)->default(0);
            $table->decimal('ganancia_neta_ves', 20, 2)->default(0);
            $table->string('referencia', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('estatus')->default('sin_verificar');
            $table->string('estado')->default('en_espera');
            $table->string('estado_pool')->default('pendiente');
            $table->foreignId('pagador_id')->nullable()->constrained('users');
            $table->timestamp('asignada_at')->nullable();
            $table->timestamp('pagada_at')->nullable();
            $table->timestamp('cancelada_at')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamp('verificado_at')->nullable();
            $table->foreignId('verificado_por_id')->nullable()->constrained('users');
            $table->string('origen')->default('manual');
            $table->string('origen_referencia', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operacion_id')->constrained('operaciones');
            $table->foreignId('cuenta_id')->constrained('cuentas');
            $table->foreignId('moneda_id')->constrained('monedas');
            $table->decimal('monto', 20, 4);
            $table->decimal('tasa_a_usd', 20, 8);
            $table->decimal('monto_usd_equivalente', 20, 4);
            $table->unsignedSmallInteger('orden')->default(1);
            $table->timestamps();
        });
        Schema::create('transacciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operacion_id')->constrained('operaciones');
            $table->foreignId('cuenta_origen_id')->constrained('cuentas');
            $table->foreignId('cuenta_destino_id')->constrained('cuentas');
            $table->foreignId('moneda_id')->constrained('monedas');
            $table->decimal('monto', 20, 4);
            $table->string('estado', 50)->default('pendiente');
            $table->text('motivo_rechazo')->nullable();
            $table->string('comprobante')->nullable();
            $table->timestamp('validada_en')->nullable();
            $table->foreignId('validada_por_id')->nullable()->constrained('users');
            $table->integer('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacciones');
        Schema::dropIfExists('movimientos');
        Schema::dropIfExists('operaciones');
        Schema::dropIfExists('tasas_mercado');
        Schema::dropIfExists('tasas_diarias');
        Schema::dropIfExists('cuentas');
        Schema::dropIfExists('tipos_operacion');
        Schema::dropIfExists('categorias_gasto');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('users');
        Schema::dropIfExists('bancos');
        Schema::dropIfExists('titulares');
        Schema::dropIfExists('monedas');
    }
};
