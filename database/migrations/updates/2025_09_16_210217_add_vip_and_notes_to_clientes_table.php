<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Si usas MySQL, puedes usar ->after('genero'); en otros motores quítalo
            if (!Schema::hasColumn('clientes', 'es_vip')) {
                $table->boolean('es_vip')->default(false)->after('genero');
            }
            if (!Schema::hasColumn('clientes', 'notas_personal')) {
                $table->text('notas_personal')->nullable()->after('es_vip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            if (Schema::hasColumn('clientes', 'notas_personal')) {
                $table->dropColumn('notas_personal');
            }
            if (Schema::hasColumn('clientes', 'es_vip')) {
                $table->dropColumn('es_vip');
            }
        });
    }
};
