<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('habitaciones', function (Blueprint $table) {
      if (!Schema::hasColumn('habitaciones', 'precio_base')) {
        $table->decimal('precio_base', 10, 2)->default(0)->after('descripcion');
      }
      if (!Schema::hasColumn('habitaciones', 'moneda')) {
        // Simple y práctico: código ISO (CRC, USD…). Si ya tienes "monedas", puedes cambiar a FK.
        $table->string('moneda', 3)->default('CRC')->after('precio_base');
      }
    });
  }
  public function down(): void {
    Schema::table('habitaciones', function (Blueprint $table) {
      if (Schema::hasColumn('habitaciones', 'moneda')) {
        $table->dropColumn('moneda');
      }
      if (Schema::hasColumn('habitaciones', 'precio_base')) {
        $table->dropColumn('precio_base');
      }
    });
  }
};
