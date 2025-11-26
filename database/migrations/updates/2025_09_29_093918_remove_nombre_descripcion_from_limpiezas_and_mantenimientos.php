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
        Schema::table('limpiezas', function (Blueprint $table) {
            $table->dropColumn(['nombre', 'descripcion']);
        });

        // Quitar columnas de tabla mantenimientos
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dropColumn(['nombre', 'descripcion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('limpiezas', function (Blueprint $table) {
            $table->string('nombre', 60)->after('id_limpieza');
            $table->string('descripcion', 250)->nullable()->after('nombre');
        });

        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->string('nombre', 50)->unique()->after('id_mantenimiento');
            $table->string('descripcion', 250)->nullable()->after('nombre');
        });
    }
};
