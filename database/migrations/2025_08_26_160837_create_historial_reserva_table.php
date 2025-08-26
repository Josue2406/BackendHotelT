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
        Schema::create('historial_reserva', function (Blueprint $table) {
            $table->id('id_hist_res');                // INT PK
        $table->integer('id_reserva');                    // INT NOT NULL (FK futura)
        $table->integer('id_usuario');                    // INT NOT NULL (FK futura)
        $table->string('campo', 80);                      // NOT NULL
        $table->string('valor_anterior', 300)->nullable();
        $table->string('valor_nuevo', 300)->nullable();
        $table->string('motivo', 200)->nullable();
        $table->dateTime('timestamp');                    // NOT NULL

        $table->index('id_reserva');
        $table->index('id_usuario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_reserva');
    }
};
