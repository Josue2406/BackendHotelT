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
       Schema::table('mantenimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_estado_hab')->nullable()->after('id_habitacion');

            $table->foreign('id_estado_hab')
                ->references('id_estado_hab')
                ->on('estado_habitacions')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dropForeign(['id_estado_hab']);
            $table->dropColumn('id_estado_hab');
        });
    }
};
