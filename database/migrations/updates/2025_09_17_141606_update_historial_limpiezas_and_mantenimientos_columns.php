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
        Schema::table('historial_limpiezas', function (Blueprint $table) {
            $table->text('valor_anterior')->nullable()->change();
            $table->text('valor_nuevo')->nullable()->change();
        });

        Schema::table('historial_mantenimientos', function (Blueprint $table) {
            $table->text('valor_anterior')->nullable()->change();
            $table->text('valor_nuevo')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historial_limpiezas', function (Blueprint $table) {
            $table->string('valor_anterior', 300)->nullable()->change();
            $table->string('valor_nuevo', 300)->nullable()->change();
        });

        Schema::table('historial_mantenimientos', function (Blueprint $table) {
            $table->string('valor_anterior', 300)->nullable()->change();
            $table->string('valor_nuevo', 300)->nullable()->change();
        });
    }
};
