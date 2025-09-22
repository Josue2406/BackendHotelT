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
        Schema::create('cliente_preferencias', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('id_cliente');

            // enums según FE
            $t->enum('bed_type', ['single','double','queen','king','twin'])->nullable();
            $t->enum('floor', ['low','middle','high'])->nullable();
            $t->enum('view', ['ocean','mountain','city','garden'])->nullable();

            $t->boolean('smoking_allowed')->nullable(); // true = prefiere fumadores
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
        Schema::dropIfExists('cliente_preferencias');
    }
    
};
