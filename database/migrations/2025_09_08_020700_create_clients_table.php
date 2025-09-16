<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('clientes', function (Blueprint $table) {
            $table->bigIncrements('id_cliente');
            $table->string('nombre', 60);
            $table->string('apellido1', 60);
            $table->string('apellido2', 60)->nullable();
            $table->string('email', 50)->unique();
            $table->string('telefono', 50)->unique();
            $table->unsignedBigInteger('id_tipo_doc')->nullable();
          $table->string('numero_doc', 40)->nullable();
            $table->string('nacionalidad', 60);
            $table->string('direccion', 200)->nullable();
            $table->date('fecha_nacimiento', 40)->nullable();
            $table->string('genero')->nullable();
            $table->timestamps();

            $table->foreign('id_tipo_doc')->references('id_tipo_doc')->on('tipo_doc')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void {
        Schema::dropIfExists('clientes');
    }
};
