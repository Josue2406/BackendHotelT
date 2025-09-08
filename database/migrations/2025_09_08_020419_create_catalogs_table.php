<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // rols
        Schema::create('rols', function (Blueprint $table) {
            $table->bigIncrements('id_rol');
            $table->string('nombre', 50);
            $table->string('descripcion', 250);
            $table->timestamps();
        });

        // estado_habitacions
        Schema::create('estado_habitacions', function (Blueprint $table) {
            $table->bigIncrements('id_estado_hab');
            $table->string('nombre', 30)->unique();
            $table->string('descripcion', 100)->nullable();
            $table->timestamps();
        });

        // tipos_habitacion
        Schema::create('tipos_habitacion', function (Blueprint $table) {
            $table->bigIncrements('id_tipo_hab');
            $table->string('nombre', 60)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });

        // amenidads
        Schema::create('amenidads', function (Blueprint $table) {
            $table->bigIncrements('id_amenidad');
            $table->string('nombre', 60)->unique();
            $table->string('descripcion', 60);
            $table->timestamps();
        });

        // fuentes (catálogo único)
        Schema::create('fuentes', function (Blueprint $table) {
            $table->bigIncrements('id_fuente');
            $table->string('nombre', 100)->unique();
            $table->string('codigo', 5)->unique();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down(): void {
        Schema::dropIfExists('fuentes');
        Schema::dropIfExists('amenidads');
        Schema::dropIfExists('tipos_habitacion');
        Schema::dropIfExists('estado_habitacions');
        Schema::dropIfExists('rols');
    }
};
