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
        Schema::create('servicio', function (Blueprint $table) {
            $table->id('id_servicio');                // INT PK
        $table->string('nombre', 80)->unique();           // UNIQUE, NOT NULL
        $table->decimal('precio', 10, 2);                 // NOT NULL
        $table->string('descripcion', 200)->nullable();   // NULL

        // Opcional:
        // $table->check('precio >= 0');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicio');
    }
};
