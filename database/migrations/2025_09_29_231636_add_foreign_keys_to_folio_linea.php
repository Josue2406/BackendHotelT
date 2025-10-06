<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        // Asegura columnas (si viniera de antes)
        Schema::table('folio_linea', function (Blueprint $table) {
            if (!Schema::hasColumn('folio_linea','id_folio')) {
                $table->unsignedBigInteger('id_folio')->after('id_folio_linea');
            }
            if (!Schema::hasColumn('folio_linea','id_cliente')) {
                $table->unsignedBigInteger('id_cliente')->nullable()->after('id_folio');
            }
        });

        // === Índice compuesto: solo si NO existe (por nombre o por columnas) ===
        if (!$this->indexExists('folio_linea', 'folio_linea_folio_cliente_idx')
            && !$this->indexOnColumnsExists('folio_linea', ['id_folio','id_cliente'])) {
            Schema::table('folio_linea', function (Blueprint $table) {
                $table->index(['id_folio','id_cliente'], 'folio_linea_folio_cliente_idx');
            });
        }

        // Limpia FKs previas (si las hubiera) sin asumir el nombre
        $this->dropForeignIfExists('folio_linea', 'id_folio');
        $this->dropForeignIfExists('folio_linea', 'id_cliente');

        // Crea FKs correctas
        Schema::table('folio_linea', function (Blueprint $table) {
            $table->foreign('id_folio')
                  ->references('id_folio')->on('folio')
                  ->cascadeOnDelete();

            $table->foreign('id_cliente')
                  ->references('id_cliente')->on('clientes')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        $this->dropForeignIfExists('folio_linea', 'id_folio');
        $this->dropForeignIfExists('folio_linea', 'id_cliente');

        // (Opcional) eliminar el índice si existe
        if ($this->indexExists('folio_linea', 'folio_linea_folio_cliente_idx')) {
            Schema::table('folio_linea', function (Blueprint $table) {
                $table->dropIndex('folio_linea_folio_cliente_idx');
            });
        }
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$table, $column]);

        foreach ($constraints as $c) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$c->CONSTRAINT_NAME}`");
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
        // Verifica si existe cualquier índice exactamente sobre esas columnas y en ese orden
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
