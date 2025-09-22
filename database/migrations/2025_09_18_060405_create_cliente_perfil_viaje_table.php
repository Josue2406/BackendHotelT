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
        Schema::create('cliente_perfil_viaje', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('id_cliente');

            $t->enum('typical_travel_group', ['solo','couple','family','business_group','friends'])->nullable();
            $t->boolean('has_children')->nullable();
            $t->json('children_age_ranges')->nullable(); // ej. ["0-2","3-7"]
            $t->unsignedTinyInteger('preferred_occupancy')->nullable(); // 1..10
            $t->boolean('needs_connected_rooms')->nullable();

            $t->timestamps();

            $t->foreign('id_cliente')
              ->references('id_cliente')->on('clientes')
              ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_perfil_viaje');
    }
};
