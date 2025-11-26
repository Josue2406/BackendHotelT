<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // (Opcional) convierte strings vacíos a NULL para no chocar con NOT NULL previos
        DB::table('clientes')->where('apellido2','')->update(['apellido2'=>null]);
        DB::table('clientes')->where('telefono','')->update(['telefono'=>null]);
        DB::table('clientes')->where('numero_doc','')->update(['numero_doc'=>null]);
        DB::table('clientes')->where('nacionalidad','')->update(['nacionalidad'=>null]);
        DB::table('clientes')->where('direccion','')->update(['direccion'=>null]);
        DB::table('clientes')->where('genero','')->update(['genero'=>null]);
        DB::table('clientes')->where('notas_personal','')->update(['notas_personal'=>null]);

        Schema::table('clientes', function (Blueprint $table) {
            // Haz NULL solo lo que realmente quieres que sea opcional:
            $table->string('apellido2', 60)->nullable()->change();
            $table->string('telefono', 50)->nullable()->change();        // puede seguir con UNIQUE
            $table->unsignedBigInteger('id_tipo_doc')->nullable()->change();
            $table->string('numero_doc', 40)->nullable()->change();
            $table->string('nacionalidad', 60)->nullable()->change();
            $table->string('direccion', 200)->nullable()->change();
            $table->date('fecha_nacimiento')->nullable()->change();
            $table->string('genero', 1)->nullable()->change();
            $table->text('notas_personal')->nullable()->change();
            // Si necesitas otro, agrégalo aquí.
        });
    }

    public function down(): void
    {
        // Revertir (si quieres volver a NOT NULL). Ajusta a tus reglas reales:
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('apellido2', 60)->nullable(false)->change();
            $table->string('telefono', 50)->nullable(false)->change();
            $table->unsignedBigInteger('id_tipo_doc')->nullable(false)->change();
            $table->string('numero_doc', 40)->nullable(false)->change();
            $table->string('nacionalidad', 60)->nullable(false)->change();
            $table->string('direccion', 200)->nullable(false)->change();
            $table->date('fecha_nacimiento')->nullable(false)->change();
            $table->string('genero', 1)->nullable(false)->change();
            $table->text('notas_personal')->nullable(false)->change();
        });
    }
};
