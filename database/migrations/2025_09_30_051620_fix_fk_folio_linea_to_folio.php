<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('folio_linea', function (Blueprint $table) {
            // elimina FK previa (si existiera) sin reventar
            try { $table->dropForeign(['id_folio']); } catch (\Throwable $e) {}

            // crea FK correcta → tabla 'folio' (singular)
            $table->foreign('id_folio')
                ->references('id_folio')->on('folio')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('folio_linea', function (Blueprint $table) {
            try { $table->dropForeign(['id_folio']); } catch (\Throwable $e) {}
            // (si necesitas revertir a 'folios', agrégalo aquí)
        });
    }
};
