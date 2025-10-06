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
            $table->string('email', 150)->unique();
            $table->string('password');// hash bcrypt
            $table->rememberToken(); // crea remember_token
            $table->string('telefono', 50)->nullable()->unique();
            $table->unsignedBigInteger('id_tipo_doc')->nullable();
          $table->string('numero_doc', 40)->nullable()->index(); // o ->unique()
             $table->string('nacionalidad', 60)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->date('fecha_nacimiento')->nullable();          // ← sin “40”
            $table->string('genero', 1)->nullable();
             // Campos que tu modelo expone
            $table->boolean('es_vip')->default(false);
            $table->text('notas_personal')->nullable();
            $table->timestamps();

            $table->foreign('id_tipo_doc')->references('id_tipo_doc')->on('tipo_doc')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void {
        Schema::dropIfExists('clientes');
    }
};
