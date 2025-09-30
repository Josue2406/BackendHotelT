<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('transaccion_pago', function (Blueprint $table) {
            // monto para poder sumar pagos
            if (!Schema::hasColumn('transaccion_pago', 'monto')) {
                $table->decimal('monto', 12, 2)->default(0)->after('id_tipo_transaccion');
            }

            // cliente para poder asignar pagos a persona (nullable = pagos generales)
            if (!Schema::hasColumn('transaccion_pago', 'id_cliente')) {
                $table->unsignedBigInteger('id_cliente')->nullable()->after('id_folio');
                $table->index('id_cliente', 'tp_id_cliente_idx');

                // FK (ajusta si tu PK en clientes tiene otro nombre/tipo)
                try {
                    $table->foreign('id_cliente')
                        ->references('id_cliente')->on('clientes')
                        ->nullOnDelete();
                } catch (\Throwable $e) {
                    // ignora si ya existe/engine no soporta/orden, etc.
                }
            }
        });
    }

    public function down(): void {
        Schema::table('transaccion_pago', function (Blueprint $table) {
            try { $table->dropForeign(['id_cliente']); } catch (\Throwable $e) {}
            try { $table->dropIndex('tp_id_cliente_idx'); } catch (\Throwable $e) {}

            if (Schema::hasColumn('transaccion_pago', 'id_cliente')) {
                $table->dropColumn('id_cliente');
            }
            if (Schema::hasColumn('transaccion_pago', 'monto')) {
                $table->dropColumn('monto');
            }
        });
    }
};
