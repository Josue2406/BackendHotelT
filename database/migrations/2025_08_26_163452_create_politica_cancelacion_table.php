<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('politica_cancelacion', function (Blueprint $table) {
            $table->id('id_politica');                // INT PK
            $table->string('nombre', 80)->unique();           // UNIQUE, NOT NULL
            $table->string('regla_ventana', 120);             // NOT NULL (ej: "48h antes del check-in")
            $table->string('penalidad_tipo', 20);             // NOT NULL (ej: monto, porcentaje, no_show_noche)
            $table->decimal('penalidad_valor', 10, 2);        // NOT NULL (>= 0)
            $table->string('descripcion', 200)->nullable();   // NULL

            // Opcional: si usas MySQL 8+, podrías activar CHECKs
            // $table->check("penalidad_tipo IN ('monto','porcentaje','no_show_noche')");
            // $table->check('penalidad_valor >= 0');
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('politica_cancelacion');
    }
};
