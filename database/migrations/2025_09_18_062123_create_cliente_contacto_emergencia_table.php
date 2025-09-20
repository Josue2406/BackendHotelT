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
       Schema::create('cliente_contacto_emergencia', function (Blueprint $t) {
    $t->id();
    $t->unsignedBigInteger('id_cliente');
    $t->string('name', 100)->nullable();
    $t->string('relationship', 60)->nullable();
    $t->string('phone', 50)->nullable();
    $t->string('email', 150)->nullable();
    $t->timestamps();

    $t->foreign('id_cliente')->references('id_cliente')->on('clientes')->cascadeOnDelete();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_contacto_emergencia');
    }
};
