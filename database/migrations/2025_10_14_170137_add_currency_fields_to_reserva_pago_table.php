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
        Schema::table('reserva_pago', function (Blueprint $table) {
            // Verificar y agregar columnas solo si no existen
            if (!Schema::hasColumn('reserva_pago', 'id_moneda')) {
                $table->unsignedBigInteger('id_moneda')->nullable()->after('monto');
                $table->foreign('id_moneda')->references('id_moneda')->on('moneda')->onDelete('set null');
            }

            if (!Schema::hasColumn('reserva_pago', 'tipo_cambio')) {
                $table->decimal('tipo_cambio', 12, 6)->default(1.000000)->after('monto')
                    ->comment('Tipo de cambio aplicado: 1 USD = X moneda');
            }

            if (!Schema::hasColumn('reserva_pago', 'monto_usd')) {
                $table->decimal('monto_usd', 10, 2)->nullable()->after('monto')
                    ->comment('Monto original en dólares antes de conversión');
            }

            if (!Schema::hasColumn('reserva_pago', 'referencia')) {
                $table->string('referencia', 100)->nullable()->after('monto');
            }

            if (!Schema::hasColumn('reserva_pago', 'notas')) {
                $table->text('notas')->nullable()->after('monto');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reserva_pago', function (Blueprint $table) {
            $table->dropForeign(['id_moneda']);
            $table->dropColumn(['id_moneda', 'tipo_cambio', 'monto_usd', 'referencia', 'notas']);
        });
    }
};