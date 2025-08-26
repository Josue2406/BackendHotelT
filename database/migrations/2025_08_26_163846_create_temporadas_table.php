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
        Schema::create('temporadas', function (Blueprint $table) {
            $table->id('id_temporada'); 
            $table->string('campo', 60)->unique();
            $table->date('fecha_ini');
            $table->date('fecha_fin');

            // Restricción: fecha_fin > fecha_ini
            //$table->check('fecha_fin > fecha_ini');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temporadas');
    }
};
