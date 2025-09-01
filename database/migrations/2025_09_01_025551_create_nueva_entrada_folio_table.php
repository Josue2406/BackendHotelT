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
        Schema::create('nueva_entrada_folio', function (Blueprint $table) {
            $table->id('id_nueva_entrada_folio');    // INT PK (Auto-incremental)
            $table->unsignedBigInteger('id_folio');  // INT (FK hacia folio)
            $table->unsignedBigInteger('id_tipo_entrada'); // INT (FK hacia tipo_entrada)
            $table->unsignedBigInteger('id_tipo_concepto'); // INT (FK hacia tipo_concepto)
            $table->string('descripcion', 255)->nullable(); // Descripción
            $table->decimal('monto', 10, 2);          // Monto
            $table->date('fecha');                    // Fecha
            $table->unsignedBigInteger('id_usuario'); // INT (FK hacia usuarios)
            $table->integer('cantidad');              // Cantidad
            $table->timestamps();                    // created_at y updated_at

            // Claves foráneas
            $table->foreign('id_folio')
                  ->references('id_folio')
                  ->on('folio')  // Relación con la tabla folio
                  ->onDelete('cascade'); // Acción al eliminar un folio

            $table->foreign('id_tipo_entrada')
                  ->references('id_tipo_entrada_folio')
                  ->on('tipo_entrada')  // Relación con la tabla tipo_entrada
                  ->onDelete('cascade'); // Acción al eliminar un tipo de entrada

            $table->foreign('id_tipo_concepto')
                  ->references('id_tipo_concepto_folio')
                  ->on('tipo_concepto')  // Relación con la tabla tipo_concepto
                  ->onDelete('cascade'); // Acción al eliminar un tipo de concepto

            $table->foreign('id_usuario')
                  ->references('id_usuario')
                  ->on('usuarios')  // Relación con la tabla usuarios
                  ->onDelete('cascade'); // Acción al eliminar un usuario
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nueva_entrada_folio', function (Blueprint $table) {
            // Eliminar las claves foráneas
            $table->dropForeign(['id_folio']);
            $table->dropForeign(['id_tipo_entrada']);
            $table->dropForeign(['id_tipo_concepto']);
            $table->dropForeign(['id_usuario']);
        });

        Schema::dropIfExists('nueva_entrada_folio');
    }
};
