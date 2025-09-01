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
        Schema::create('factura', function (Blueprint $table) {
            $table->id('id_factura');            // INT PK (Auto-incremental)
            $table->unsignedBigInteger('id_folio'); // INT (FK hacia folio)
            $table->string('concepto', 255);     // Concepto de la factura
            $table->decimal('monto', 10, 2);     // Monto de la factura
            $table->date('fechaFactura');        // Fecha de la factura
            $table->date('fechaConsumo');       // Fecha de consumo
            $table->integer('cantidad');         // Cantidad de productos o servicios
            $table->timestamps();               // created_at y updated_at

            // Claves foráneas
            $table->foreign('id_folio')
                  ->references('id_folio')
                  ->on('folio')            // Relación con la tabla folio
                  ->onDelete('cascade');   // Acción al eliminar un folio
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factura', function (Blueprint $table) {
            // Eliminar las claves foráneas
            $table->dropForeign(['id_folio']);
        });

        Schema::dropIfExists('factura');
    }
};
