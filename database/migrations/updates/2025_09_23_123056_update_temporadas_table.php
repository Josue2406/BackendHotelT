<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('temporadas', function (Blueprint $table) {
            // Renombrar columna "campo" a "nombre"
            if (Schema::hasColumn('temporadas', 'campo')) {
                $table->renameColumn('campo', 'nombre');
            }

            // Cambiar tipo y longitud si "campo" era otro tipo (ejemplo TEXT)
            $table->string('nombre', 100)->change();

            // Agregar prioridad y activo si no existen
            if (!Schema::hasColumn('temporadas', 'prioridad')) {
                $table->tinyInteger('prioridad')->default(1)->after('fecha_fin');
            }

            if (!Schema::hasColumn('temporadas', 'activo')) {
                $table->boolean('activo')->default(1)->after('prioridad');
            }
        });
    }

    public function down(): void
    {
        Schema::table('temporadas', function (Blueprint $table) {
            // Revertir cambios
            if (Schema::hasColumn('temporadas', 'nombre')) {
                $table->renameColumn('nombre', 'campo');
            }

            if (Schema::hasColumn('temporadas', 'prioridad')) {
                $table->dropColumn('prioridad');
            }

            if (Schema::hasColumn('temporadas', 'activo')) {
                $table->dropColumn('activo');
            }
        });
    }
};
