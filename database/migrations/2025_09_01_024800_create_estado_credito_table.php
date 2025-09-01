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
        Schema::create('estado_credito', function (Blueprint $table) {
            $table->id('id_estado_credito');     // INT PK (Auto-incremental)
            $table->string('nombre', 100);        // Nombre del estado de crédito
            $table->string('descripcion', 255)->nullable(); // Descripción del estado (opcional)
            $table->timestamps();                // created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estado_credito');
    }
};
