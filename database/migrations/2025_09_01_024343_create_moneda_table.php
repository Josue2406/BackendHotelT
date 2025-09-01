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
        Schema::create('moneda', function (Blueprint $table) {
            $table->id('id_moneda');       // INT PK (Auto-incremental)
            $table->string('codigo', 10);   // Código de la moneda (ej: USD, EUR, etc.)
            $table->string('nombre', 100);  // Nombre completo de la moneda (ej: Dólar, Euro, etc.)
            $table->timestamps();          // created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moneda');
    }
};
