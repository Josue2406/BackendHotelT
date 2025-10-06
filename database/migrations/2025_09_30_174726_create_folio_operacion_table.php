<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('folio_operacion', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_folio');
            $table->string('operacion_uid', 64); // enviado por el front
            $table->string('tipo', 32);          // 'distribucion', etc.
            $table->decimal('total', 12, 2)->default(0);
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['operacion_uid', 'id_folio', 'tipo'], 'u_folio_operacion_uid');
            $table->index('id_folio', 'idx_folio_operacion_folio');

            // FK opcional si quieres: ->on('folio')
            // $table->foreign('id_folio')->references('id_folio')->on('folio')->cascadeOnDelete();
        });
    }
    public function down(): void {
        Schema::dropIfExists('folio_operacion');
    }
};
