<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tipos_habitacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80)->unique();
            $table->string('codigo', 20)->unique();            // EJ: STD, DLX, STE
            $table->unsignedTinyInteger('capacidad')->default(1);
            $table->decimal('tarifa_base', 10, 2)->default(0); // tarifa por noche
            $table->json('amenidades')->nullable();            // JSON: ["wifi","tv","ac"]
            $table->text('descripcion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('tipos_habitacion');
    }
};


