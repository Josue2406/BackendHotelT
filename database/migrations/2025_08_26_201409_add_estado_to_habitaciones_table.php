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
        Schema::table('habitaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('id_estado_hab')->after('id');

            $table->foreign('id_estado_hab')
                ->references('id_estado_hab')->on('estado_habitacions')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('habitaciones', function (Blueprint $table) {
            $table->dropForeign(['id_estado_hab']);
            $table->dropColumn('id_estado_hab');
        });
    }
};
