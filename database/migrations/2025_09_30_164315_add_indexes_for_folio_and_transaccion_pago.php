<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        // ---------- folio_linea ----------
        // Índice compuesto (id_folio, id_cliente) si no existe
        if (!$this->indexExists('folio_linea', 'folio_linea_folio_cliente_idx')
            && !$this->indexOnColumnsExists('folio_linea', ['id_folio','id_cliente'])) {
            Schema::table('folio_linea', function (Blueprint $table) {
                $table->index(['id_folio','id_cliente'], 'folio_linea_folio_cliente_idx');
            });
        }

        // ---------- transaccion_pago ----------
        if (Schema::hasTable('transaccion_pago')) {
            // 1) idx por id_folio (si no existe)
            if (!$this->indexExists('transaccion_pago', 'tp_id_folio_idx')
                && !$this->indexOnColumnsExists('transaccion_pago', ['id_folio'])) {
                Schema::table('transaccion_pago', function (Blueprint $table) {
                    $table->index('id_folio', 'tp_id_folio_idx');
                });
            }

            // 2) idx por id_cliente (si existe la columna y no existe el índice)
            if (Schema::hasColumn('transaccion_pago', 'id_cliente')
                && !$this->indexExists('transaccion_pago', 'tp_id_cliente_idx')
                && !$this->indexOnColumnsExists('transaccion_pago', ['id_cliente'])) {
                Schema::table('transaccion_pago', function (Blueprint $table) {
                    $table->index('id_cliente', 'tp_id_cliente_idx');
                });
            }

            // 3) índice compuesto (id_folio, id_cliente) para joins/filtros combinados
            if (Schema::hasColumn('transaccion_pago', 'id_cliente')
                && !$this->indexExists('transaccion_pago', 'tp_folio_cliente_idx')
                && !$this->indexOnColumnsExists('transaccion_pago', ['id_folio','id_cliente'])) {
                Schema::table('transaccion_pago', function (Blueprint $table) {
                    $table->index(['id_folio','id_cliente'], 'tp_folio_cliente_idx');
                });
            }
        }
    }

    public function down(): void
    {
        // folio_linea
        if ($this->indexExists('folio_linea', 'folio_linea_folio_cliente_idx')) {
            Schema::table('folio_linea', function (Blueprint $table) {
                $table->dropIndex('folio_linea_folio_cliente_idx');
            });
        }

        // transaccion_pago
        if (Schema::hasTable('transaccion_pago')) {
            foreach (['tp_folio_cliente_idx','tp_id_cliente_idx','tp_id_folio_idx'] as $idx) {
                if ($this->indexExists('transaccion_pago', $idx)) {
                    Schema::table('transaccion_pago', function (Blueprint $table) use ($idx) {
                        $table->dropIndex($idx);
                    });
                }
            }
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $rows = DB::select("
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND INDEX_NAME = ?
            LIMIT 1
        ", [$table, $indexName]);

        return !empty($rows);
    }

    private function indexOnColumnsExists(string $table, array $columnsInOrder): bool
    {
        // ¿Existe algún índice exactamente con estas columnas y en ese orden?
        $rows = DB::select("
            SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS cols
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
            GROUP BY INDEX_NAME
        ", [$table]);

        $needle = implode(',', $columnsInOrder);
        foreach ($rows as $r) {
            if (strcasecmp($r->cols ?? '', $needle) === 0) return true;
        }
        return false;
    }
};
