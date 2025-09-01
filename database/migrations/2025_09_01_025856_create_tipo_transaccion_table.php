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
        Schema::create('tipo_transaccion', function (Blueprint $table) {
            $table->id('id_tipo_transaccion');   // INT PK (Auto-incremental)
            $table->string('nombre', 100);        // Nombre del tipo de transacción
            $table->string('descripcion', 255)->nullable(); // Descripción del tipo de transacción (opcional)
            $table->timestamps();                // created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_transaccion');
    }
};
