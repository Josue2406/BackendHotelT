<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id_usuario');
            $table->unsignedBigInteger('id_rol');
            $table->string('nombre', 60);
            $table->string('apellido1', 60);
            $table->string('apellido2', 60)->nullable();
            $table->string('email', 120)->unique();
            $table->string('password', 255);
            $table->string('telefono', 60)->nullable();
            $table->timestamps();

            $table->foreign('id_rol')->references('id_rol')->on('rols')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('users');
    }
};
