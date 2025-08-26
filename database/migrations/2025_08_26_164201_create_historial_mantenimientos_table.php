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
        Schema::create('historial_mantenimientos', function (Blueprint $table) {
            $table->id('id_historial_mant');
            //$table->unsignedBigInteger('id_mantenimiento');
            //$table->unsignedBigInteger('actor_id');

            $table->string('evento', 100);
            $table->dateTime('fecha')->useCurrent();
            $table->string('valor_anterior', 100)->nullable();
            $table->string('valor_nuevo', 100)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_mantenimientos');
    }
};
