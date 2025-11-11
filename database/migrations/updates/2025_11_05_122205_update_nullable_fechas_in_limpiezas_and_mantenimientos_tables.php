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
            $table->dateTime('fecha_inicio')->nullable()->change();
            $table->dateTime('fecha_final')->nullable()->change();
        });

        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dateTime('fecha_inicio')->nullable()->change();
            $table->dateTime('fecha_final')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('limpiezas', function (Blueprint $table) {
            $table->dateTime('fecha_inicio')->nullable(false)->change();
            $table->dateTime('fecha_final')->nullable()->change();
        });

        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dateTime('fecha_inicio')->nullable(false)->change();
            $table->dateTime('fecha_final')->nullable()->change();
        });
    }
};