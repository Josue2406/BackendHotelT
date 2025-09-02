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
        Schema::table('check_ins', function (Blueprint $table) {
            $table->foreignId('id_asignacion')
                  ->constrained('asignacion_habitacions', 'id_asignacion')
                  ->onDelete('cascade')
                  ->after('observacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            $table->dropForeign(['id_asignacion']);
            $table->dropColumn('id_asignacion');
        });
    }
};
