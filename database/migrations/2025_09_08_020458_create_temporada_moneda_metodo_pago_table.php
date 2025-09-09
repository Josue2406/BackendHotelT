<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // temporadas
        Schema::create('temporadas', function (Blueprint $table) {
            $table->bigIncrements('id_temporada');
            $table->string('campo', 60)->unique();
            $table->date('fecha_ini');
            $table->date('fecha_fin');
            $table->timestamps();
        });

        // moneda
        Schema::create('moneda', function (Blueprint $table) {
            $table->bigIncrements('id_moneda');
            $table->string('codigo', 10);
            $table->string('nombre', 100);
            $table->timestamps();
        });

        // metodo_pago
        Schema::create('metodo_pago', function (Blueprint $table) {
            $table->bigIncrements('id_metodo_pago');
            $table->unsignedBigInteger('id_moneda');
            $table->string('nombre', 100)->unique();
            $table->timestamps();

            $table->foreign('id_moneda')->references('id_moneda')->on('moneda')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('metodo_pago');
        Schema::dropIfExists('moneda');
        Schema::dropIfExists('temporadas');
    }
};
