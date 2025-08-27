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
        Schema::table('usuarios', function (Blueprint $table) {
            $table->unsignedBigInteger('id_rol')->after('id_usuario');

            $table->index('id_rol', 'idx_usuario_rol');

            // FK hacia roles
            $table->foreign('id_rol', 'fk_usuario_rol')
                ->references('id_rol')->on('rols')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropForeign('fk_usuario_rol');
            $table->dropIndex('idx_usuario_rol');
            $table->dropColumn('id_rol');
        });
    }
};
