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
        Schema::create('cargo_reserva', function (Blueprint $table) {
            $table->id('id_cargo');                  // INT PK (AI)
            $table->integer('id_reserva_pago');              // INT (FK futura)
            $table->string('tipo_cargo', 50);                // cambio habitación, cancela reserva, etc.
            $table->decimal('monto', 10, 2);                 // decimal NOT NULL
            $table->timestamp('fecha')->useCurrent();        // fecha del cargo (por defecto NOW())

            $table->index('id_reserva_pago');                // índice opcional
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cargo_reserva');
    }
};
