<?php



            // Índices útiles

// database/migrations/2025_09_29_205942_create_folio_linea_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('folio_linea')) {
            // Crear desde cero
            Schema::create('folio_linea', function (Blueprint $table) {
                $table->bigIncrements('id_folio_linea');
                $table->unsignedBigInteger('id_folio');
                $table->unsignedBigInteger('id_cliente')->nullable();
                $table->string('descripcion')->nullable();
                $table->decimal('monto', 12, 2)->default(0);
                $table->timestamps();

                $table->index(['id_folio', 'id_cliente']);
            });
        } else {
            // Ya existe: asegurar columnas mínimas
            Schema::table('folio_linea', function (Blueprint $table) {
                if (!Schema::hasColumn('folio_linea', 'id_folio_linea')) {
                    $table->bigIncrements('id_folio_linea');
                }
                if (!Schema::hasColumn('folio_linea', 'id_folio')) {
                    $table->unsignedBigInteger('id_folio')->after('id_folio_linea');
                }
                if (!Schema::hasColumn('folio_linea', 'id_cliente')) {
                    $table->unsignedBigInteger('id_cliente')->nullable()->after('id_folio');
                }
                if (!Schema::hasColumn('folio_linea', 'descripcion')) {
                    $table->string('descripcion')->nullable()->after('id_cliente');
                }
                if (!Schema::hasColumn('folio_linea', 'monto')) {
                    $table->decimal('monto', 12, 2)->default(0)->after('descripcion');
                }
                if (!Schema::hasColumn('folio_linea', 'created_at')) {
                    $table->timestamps();
                }
                // índice útil (si ya existe, MySQL lo ignora si es igual)
                $table->index(['id_folio', 'id_cliente'], 'folio_linea_folio_cliente_idx');
            });
        }
    }

    public function down(): void
    {
        // Si no quieres perder datos, deja vacío o comenta el drop
        // Schema::dropIfExists('folio_linea');
    }
};
