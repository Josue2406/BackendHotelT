<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // folio
        Schema::create('folio', function (Blueprint $table) {
            $table->bigIncrements('id_folio');
            $table->unsignedBigInteger('id_reserva_hab')->nullable(); // compat
            $table->unsignedBigInteger('id_estadia')->nullable();     // walk-in
            $table->unsignedBigInteger('id_estado_folio');
            $table->decimal('total', 10, 2);
            $table->timestamps();

            $table->foreign('id_reserva_hab')->references('id_reserva_hab')->on('reserva_habitacions')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('id_estadia')->references('id_estadia')->on('estadia')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('id_estado_folio')->references('id_estado_folio')->on('estado_folio')->cascadeOnUpdate()->restrictOnDelete();
        });

        // nueva_entrada_folio
        Schema::create('nueva_entrada_folio', function (Blueprint $table) {
            $table->bigIncrements('id_nueva_entrada_folio');
            $table->unsignedBigInteger('id_folio');
            $table->unsignedBigInteger('id_tipo_entrada');
            $table->unsignedBigInteger('id_tipo_concepto');
            $table->unsignedBigInteger('id_usuario');
            $table->string('descripcion', 255)->nullable();
            $table->integer('cantidad');
            $table->decimal('monto', 10, 2);
            $table->date('fecha');
            $table->timestamps();

            $table->foreign('id_folio')->references('id_folio')->on('folio')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('id_tipo_entrada')->references('id_tipo_entrada_folio')->on('tipo_entrada')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_tipo_concepto')->references('id_tipo_concepto_folio')->on('tipo_concepto')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_usuario')->references('id_usuario')->on('users')->cascadeOnUpdate()->restrictOnDelete();
        });

        // factura
        Schema::create('factura', function (Blueprint $table) {
            $table->bigIncrements('id_factura');
            $table->unsignedBigInteger('id_folio');
            $table->string('concepto', 255);
            $table->decimal('monto', 10, 2);
            $table->date('fechaFactura');
            $table->date('fechaConsumo');
            $table->integer('cantidad');
            $table->timestamps();

            $table->foreign('id_folio')->references('id_folio')->on('folio')->cascadeOnUpdate()->restrictOnDelete();
        });

        // reserva_pago
        Schema::create('reserva_pago', function (Blueprint $table) {
            $table->bigIncrements('id_reserva_pago');
            $table->unsignedBigInteger('id_reserva');
            $table->unsignedBigInteger('id_metodo_pago')->nullable();
            $table->unsignedBigInteger('id_tipo_transaccion')->nullable();
            $table->unsignedBigInteger('id_estado_pago')->nullable();
            $table->decimal('monto', 10, 2);
            $table->dateTime('fecha_pago');
            $table->unsignedBigInteger('creado_por');
            $table->timestamps();

            $table->foreign('id_reserva')->references('id_reserva')->on('reserva')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('id_metodo_pago')->references('id_metodo_pago')->on('metodo_pago')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('id_tipo_transaccion')->references('id_tipo_transaccion')->on('tipo_transaccion')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('id_estado_pago')->references('id_estado_pago')->on('estado_pago')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('creado_por')->references('id_usuario')->on('users')->cascadeOnUpdate()->restrictOnDelete();
        });

        // cargo_reserva
        Schema::create('cargo_reserva', function (Blueprint $table) {
            $table->bigIncrements('id_cargo');
            $table->unsignedBigInteger('id_reserva_pago');
            $table->string('tipo_cargo', 50);
            $table->decimal('monto', 10, 2);
            $table->dateTime('fecha');
            $table->timestamps();

            $table->foreign('id_reserva_pago')->references('id_reserva_pago')->on('reserva_pago')->cascadeOnUpdate()->cascadeOnDelete();
        });

        // credito_respaldo
        Schema::create('credito_respaldo', function (Blueprint $table) {
            $table->bigIncrements('id_credito');
            $table->unsignedBigInteger('id_reserva_hab');
            $table->decimal('monto', 10, 2);
            $table->unsignedBigInteger('id_estado_credito');
            $table->date('fecha');
            $table->timestamps();

            $table->foreign('id_reserva_hab')->references('id_reserva_hab')->on('reserva_habitacions')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_estado_credito')->references('id_estado_credito')->on('estado_credito')->cascadeOnUpdate()->restrictOnDelete();
        });

        // transaccion_pago
        Schema::create('transaccion_pago', function (Blueprint $table) {
            $table->bigIncrements('id_transaccion_pago');
            $table->unsignedBigInteger('id_reserva_pago')->nullable();
            $table->unsignedBigInteger('id_metodo_pago')->nullable();
            $table->unsignedBigInteger('id_folio')->nullable();
            $table->unsignedBigInteger('id_credito')->nullable();
            $table->unsignedBigInteger('id_tipo_transaccion')->nullable(); // corregido
            $table->string('resultado', 120)->nullable();
            $table->unsignedBigInteger('id_cargo_reserva')->nullable();
            $table->timestamps();

            $table->foreign('id_reserva_pago')->references('id_reserva_pago')->on('reserva_pago')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('id_metodo_pago')->references('id_metodo_pago')->on('metodo_pago')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('id_folio')->references('id_folio')->on('folio')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('id_credito')->references('id_credito')->on('credito_respaldo')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('id_tipo_transaccion')->references('id_tipo_transaccion')->on('tipo_transaccion')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('id_cargo_reserva')->references('id_cargo')->on('cargo_reserva')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void {
        Schema::dropIfExists('transaccion_pago');
        Schema::dropIfExists('credito_respaldo');
        Schema::dropIfExists('cargo_reserva');
        Schema::dropIfExists('reserva_pago');
        Schema::dropIfExists('factura');
        Schema::dropIfExists('nueva_entrada_folio');
        Schema::dropIfExists('folio');
    }
};
