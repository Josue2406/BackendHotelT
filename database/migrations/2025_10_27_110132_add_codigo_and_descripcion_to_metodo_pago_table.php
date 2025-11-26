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
        // Verificar qué columnas existen
        $columns = \Schema::getColumnListing('metodo_pago');

        Schema::table('metodo_pago', function (Blueprint $table) use ($columns) {
            // Agregar campo codigo sin unique primero (nullable temporalmente)
            if (!in_array('codigo', $columns)) {
                $table->string('codigo', 2)->nullable()->after('id_metodo_pago');
            }

            // Agregar campo descripcion para detalles adicionales
            if (!in_array('descripcion', $columns)) {
                $table->text('descripcion')->nullable()->after('nombre');
            }

            // Agregar campo activo para habilitar/deshabilitar métodos
            if (!in_array('activo', $columns)) {
                $table->boolean('activo')->default(true)->after('descripcion');
            }

            // Agregar campo requiere_autorizacion para métodos como crédito
            if (!in_array('requiere_autorizacion', $columns)) {
                $table->boolean('requiere_autorizacion')->default(false)->after('activo');
            }
        });

        // Actualizar registros existentes con códigos temporales únicos si existen
        $metodosPago = \DB::table('metodo_pago')->get();
        if ($metodosPago->count() > 0) {
            foreach ($metodosPago as $index => $metodo) {
                if (empty($metodo->codigo)) {
                    // Generar código temporal único basado en el ID
                    $codigoTemporal = 'X' . str_pad($metodo->id_metodo_pago, 1, '0', STR_PAD_LEFT);
                    if (strlen($codigoTemporal) > 2) {
                        $codigoTemporal = substr($codigoTemporal, 0, 2);
                    }

                    \DB::table('metodo_pago')
                        ->where('id_metodo_pago', $metodo->id_metodo_pago)
                        ->update(['codigo' => $codigoTemporal]);
                }
            }
        }

        // Verificar si ya tiene el unique antes de agregarlo
        $indexes = \DB::select("SHOW INDEXES FROM metodo_pago WHERE Column_name = 'codigo'");
        $hasUnique = collect($indexes)->where('Key_name', 'metodo_pago_codigo_unique')->isNotEmpty();

        if (!$hasUnique) {
            // Ahora agregar la restricción unique
            Schema::table('metodo_pago', function (Blueprint $table) {
                $table->string('codigo', 2)->nullable(false)->unique()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('metodo_pago', function (Blueprint $table) {
            $table->dropColumn(['codigo', 'descripcion', 'activo', 'requiere_autorizacion']);
        });
    }
};
