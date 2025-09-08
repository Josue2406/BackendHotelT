<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('estado_folio', function (Blueprint $table) {
            $table->bigIncrements('id_estado_folio');
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('tipo_entrada', function (Blueprint $table) {
            $table->bigIncrements('id_tipo_entrada_folio');
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('tipo_concepto', function (Blueprint $table) {
            $table->bigIncrements('id_tipo_concepto_folio');
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('tipo_transaccion', function (Blueprint $table) {
            $table->bigIncrements('id_tipo_transaccion');
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('estado_credito', function (Blueprint $table) {
            $table->bigIncrements('id_estado_credito');
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('estado_reserva', function (Blueprint $table) {
            $table->bigIncrements('id_estado_res');
            $table->string('nombre', 20)->unique();
            $table->timestamps();
        });

        Schema::create('politica_cancelacion', function (Blueprint $table) {
            $table->bigIncrements('id_politica');
            $table->string('nombre', 80)->unique();
            $table->string('regla_ventana', 120);
            $table->string('penalidad_tipo', 20);
            $table->decimal('penalidad_valor', 10, 2);
            $table->string('descripcion', 200)->nullable();
            $table->timestamps();
        });

        Schema::create('estado_pago', function (Blueprint $table) {
            $table->bigIncrements('id_estado_pago');
            $table->string('nombre', 60)->unique();
            $table->timestamps();
        });

        Schema::create('tipo_doc', function (Blueprint $table) {
            $table->bigIncrements('id_tipo_doc');
            $table->string('nombre', 50);
        });

        Schema::create('pais', function (Blueprint $table) {
            $table->bigIncrements('id_pais');
            $table->string('codigo_iso2', 2);
            $table->string('codigo_iso3', 3);
            $table->string('nombre', 100);
        });

        Schema::create('estado_estadia', function (Blueprint $table) {
            $table->bigIncrements('id_estado_estadia');
            $table->string('codigo', 5);
            $table->string('nombre', 100);
        });
    }

    public function down(): void {
        Schema::dropIfExists('estado_estadia');
        Schema::dropIfExists('pais');
        Schema::dropIfExists('tipo_doc');
        Schema::dropIfExists('estado_pago');
        Schema::dropIfExists('politica_cancelacion');
        Schema::dropIfExists('estado_reserva');
        Schema::dropIfExists('estado_credito');
        Schema::dropIfExists('tipo_transaccion');
        Schema::dropIfExists('tipo_concepto');
        Schema::dropIfExists('tipo_entrada');
        Schema::dropIfExists('estado_folio');
    }
};
