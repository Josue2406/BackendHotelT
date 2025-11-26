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
        Schema::create('cliente_salud', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('id_cliente');

            // JSON arrays, según tu UI
            $t->json('allergies')->nullable();              // ["Nueces","Gluten",...]
            $t->json('dietary_restrictions')->nullable();   // ["Vegano","Kosher",...]
            $t->text('medical_notes')->nullable();

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
        Schema::dropIfExists('cliente_salud');
    }
};
