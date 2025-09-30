<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        // Agregar (id_folio, tipo, created_at) si no existe
        if (!$this->indexExists('folio_operacion', 'idx_folio_operacion_folio_tipo_created')
            && !$this->indexOnColumnsExists('folio_operacion', ['id_folio','tipo','created_at'])) {
            Schema::table('folio_operacion', function (Blueprint $table) {
                $table->index(['id_folio','tipo','created_at'], 'idx_folio_operacion_folio_tipo_created');
            });
        }

        // (Opcional) si agregas este índice y SIEMPRE filtras por tipo,
        // puedes quitar el índice (id_folio, created_at) porque queda cubierto:
        if ($this->indexExists('folio_operacion', 'idx_folio_operacion_folio_created')) {
            Schema::table('folio_operacion', function (Blueprint $table) {
                $table->dropIndex('idx_folio_operacion_folio_created');
            });
        }
    }

    public function down(): void
    {
        // Reponer (id_folio, created_at) si lo quitaste
        if (!$this->indexExists('folio_operacion', 'idx_folio_operacion_folio_created')) {
            Schema::table('folio_operacion', function (Blueprint $table) {
                $table->index(['id_folio','created_at'], 'idx_folio_operacion_folio_created');
            });
        }

        // Quitar (id_folio, tipo, created_at) si existe
        if ($this->indexExists('folio_operacion', 'idx_folio_operacion_folio_tipo_created')) {
            Schema::table('folio_operacion', function (Blueprint $table) {
                $table->dropIndex('idx_folio_operacion_folio_tipo_created');
            });
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
