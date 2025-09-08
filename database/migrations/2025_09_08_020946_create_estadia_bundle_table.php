<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // estadia
        Schema::create('estadia', function (Blueprint $table) {
            $table->bigIncrements('id_estadia');
            $table->unsignedBigInteger('id_reserva')->nullable(); // NULL para walk-in
            $table->unsignedBigInteger('id_cliente_titular');
            $table->unsignedBigInteger('id_fuente')->nullable(); // si quieres reflejar origen
            $table->dateTime('fecha_llegada');
            $table->date('fecha_salida');
            $table->integer('adultos')->default(1);
            $table->integer('ninos')->default(0);
            $table->integer('bebes')->default(0);
            $table->unsignedBigInteger('id_estado_estadia')->nullable(); // catálogo
            $table->timestamps();

            $table->foreign('id_reserva')->references('id_reserva')->on('reserva')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('id_cliente_titular')->references('id_cliente')->on('clientes')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_fuente')->references('id_fuente')->on('fuentes')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('id_estado_estadia')->references('id_estado_estadia')->on('estado_estadia')->nullOnDelete()->cascadeOnUpdate();
        });

        // asignacion_habitacions
        Schema::create('asignacion_habitacions', function (Blueprint $table) {
            $table->bigIncrements('id_asignacion');
            $table->unsignedBigInteger('id_hab')->nullable();
            $table->unsignedBigInteger('id_reserva')->nullable(); // compat
            $table->unsignedBigInteger('id_estadia')->nullable(); // vínculo operativo
            $table->string('origen', 30);
            $table->string('nombre', 30);
            $table->dateTime('fecha_asignacion');
            $table->integer('adultos');
            $table->integer('ninos');
            $table->integer('bebes');
            $table->timestamps();

            $table->foreign('id_hab')->references('id_habitacion')->on('habitaciones')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('id_reserva')->references('id_reserva')->on('reserva')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('id_estadia')->references('id_estadia')->on('estadia')->nullOnDelete()->cascadeOnUpdate();

            $table->index('id_hab');
            $table->index('id_reserva');
            $table->index('id_estadia');
        });

        // check_ins
        Schema::create('check_ins', function (Blueprint $table) {
            $table->bigIncrements('id_checkin');
            $table->unsignedBigInteger('id_asignacion');
            $table->dateTime('fecha_hora');
            $table->string('obervacion', 300)->nullable(); // sic del dump original
            $table->timestamps();

            $table->foreign('id_asignacion')->references('id_asignacion')->on('asignacion_habitacions')->cascadeOnUpdate()->cascadeOnDelete();
        });

        // check_outs
        Schema::create('check_outs', function (Blueprint $table) {
            $table->bigIncrements('id_checkout');
            $table->unsignedBigInteger('id_asignacion')->nullable();
            $table->dateTime('fecha_hora');
            $table->string('resultado', 30);
            $table->timestamps();

            $table->foreign('id_asignacion')->references('id_asignacion')->on('asignacion_habitacions')->nullOnDelete()->cascadeOnUpdate();
            $table->index('id_asignacion');
        });

        // hab_bloqueo_operativo
        Schema::create('hab_bloqueo_operativo', function (Blueprint $table) {
            $table->bigIncrements('id_bloqueo');
            $table->unsignedBigInteger('id_habitacion');
            $table->enum('tipo', ['OOO','OOS','INSPECCION']);
            $table->string('motivo', 200)->nullable();
            $table->dateTime('fecha_ini');
            $table->dateTime('fecha_fin');
            $table->timestamps();

            $table->foreign('id_habitacion')->references('id_habitacion')->on('habitaciones')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('hab_bloqueo_operativo');
        Schema::dropIfExists('check_outs');
        Schema::dropIfExists('check_ins');
        Schema::dropIfExists('asignacion_habitacions');
        Schema::dropIfExists('estadia');
    }
};
