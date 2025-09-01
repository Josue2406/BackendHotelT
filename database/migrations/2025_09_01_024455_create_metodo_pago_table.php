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
        Schema::create('metodo_pago', function (Blueprint $table) {
            $table->id('id_metodo_pago');      // INT PK (Auto-incremental)
            $table->unsignedBigInteger('id_moneda');  // INT (FK futura hacia moneda)
            $table->string('nombre', 100);     // Nombre del método de pago (ej: "Tarjeta de Crédito", "Efectivo", etc.)
            $table->timestamps();             // created_at y updated_at

            // Definir la clave foránea para 'id_moneda' que apunta a 'moneda'
            $table->foreign('id_moneda')
                  ->references('id_moneda')
                  ->on('moneda')
                  ->onDelete('cascade');  // Acción cuando se elimina una moneda
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('metodo_pago', function (Blueprint $table) {
            // Eliminar la clave foránea
            $table->dropForeign(['id_moneda']);
        });

        Schema::dropIfExists('metodo_pago');
    }
};
