<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('habitaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_habitacion_id')
                  ->constrained('tipos_habitacion')
                  ->cascadeOnUpdate()
                  ->restrictOnDelete();
            $table->string('nombre', 50)->unique();
            $table->string('numero', 20)->unique();   // EJ: 101, 3B
            $table->unsignedSmallInteger('piso')->default(1);
            // $table->enum('estado', ['disponible','ocupada','sucia','mantenimiento','bloqueada'])
            //     ->default('disponible');
            //$table->decimal('tarifa_noche', 10, 2)->nullable(); // si no se define, usar tarifa del tipo
            //$table->boolean('habilitada')->default(true);       // para ventas
            $table->unsignedInteger('capacidad');
            $table->string('medida');
            $table->string('descripcion');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo_habitacion_id','numero']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('habitaciones');
    }
};

