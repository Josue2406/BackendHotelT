<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // reserva
        Schema::create('reserva', function (Blueprint $table) {
            $table->bigIncrements('id_reserva');
            $table->unsignedBigInteger('id_cliente');
            $table->unsignedBigInteger('id_estado_res');
            $table->dateTime('fecha_creacion');
            $table->decimal('total_monto_reserva', 10, 2);
            $table->string('notas', 300)->nullable();
            $table->integer('adultos');
            $table->integer('ninos');
            $table->integer('bebes');
            $table->unsignedBigInteger('id_fuente')->nullable();
            $table->timestamps();

            $table->foreign('id_cliente')->references('id_cliente')->on('clientes')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_estado_res')->references('id_estado_res')->on('estado_reserva')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_fuente')->references('id_fuente')->on('fuentes')->nullOnDelete()->cascadeOnUpdate();
        });

        // reserva_politica
        Schema::create('reserva_politica', function (Blueprint $table) {
            $table->bigIncrements('id_reserva_politica');
            $table->unsignedBigInteger('id_politica');
            $table->unsignedBigInteger('id_reserva');
            $table->string('motivo', 200)->nullable();
            $table->timestamps();

            $table->foreign('id_politica')->references('id_politica')->on('politica_cancelacion')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_reserva')->references('id_reserva')->on('reserva')->cascadeOnUpdate()->cascadeOnDelete();
        });

        // servicio
        Schema::create('servicio', function (Blueprint $table) {
            $table->bigIncrements('id_servicio');
            $table->string('nombre', 80)->unique();
            $table->decimal('precio', 10, 2);
            $table->string('descripcion', 200)->nullable();
            $table->timestamps();
        });

        // reserva_servicio
        Schema::create('reserva_servicio', function (Blueprint $table) {
            $table->bigIncrements('id_reserva_serv');
            $table->unsignedBigInteger('id_reserva');
            $table->unsignedBigInteger('id_servicio');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->string('descripcion', 200)->nullable();
            $table->timestamps();

            $table->foreign('id_reserva')->references('id_reserva')->on('reserva')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('id_servicio')->references('id_servicio')->on('servicio')->cascadeOnUpdate()->restrictOnDelete();

            $table->unique(['id_reserva', 'id_servicio'], 'uq_reserva_servicio');
        });

        // reserva_habitacions
        Schema::create('reserva_habitacions', function (Blueprint $table) {
            $table->bigIncrements('id_reserva_hab');
            $table->unsignedBigInteger('id_reserva')->nullable();
            $table->unsignedBigInteger('id_habitacion')->nullable();
            $table->dateTime('fecha_llegada');
            $table->date('fecha_salida');
            $table->integer('pax_total');
            $table->timestamps();

            $table->foreign('id_reserva')->references('id_reserva')->on('reserva')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('id_habitacion')->references('id_habitacion')->on('habitaciones')->nullOnDelete()->cascadeOnUpdate();

            $table->index('id_habitacion');
            $table->index('id_reserva');
            $table->index(['id_habitacion', 'fecha_llegada', 'fecha_salida'], 'idx_hab_rango');
        });
    }

    public function down(): void {
        Schema::dropIfExists('reserva_habitacions');
        Schema::dropIfExists('reserva_servicio');
        Schema::dropIfExists('servicio');
        Schema::dropIfExists('reserva_politica');
        Schema::dropIfExists('reserva');
    }
};
