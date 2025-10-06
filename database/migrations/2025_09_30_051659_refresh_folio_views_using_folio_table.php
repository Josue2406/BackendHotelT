<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop por si quedaron restos de intentos previos
        DB::statement('DROP VIEW IF EXISTS vw_folio_resumen');
        DB::statement('DROP VIEW IF EXISTS vw_pagos_generales');
        DB::statement('DROP VIEW IF EXISTS vw_cargos_generales');
        DB::statement('DROP VIEW IF EXISTS vw_pagos_por_persona');
        DB::statement('DROP VIEW IF EXISTS vw_folio_por_persona');
        DB::statement('DROP VIEW IF EXISTS vw_folio_distribuido');
        DB::statement('DROP VIEW IF EXISTS vw_folio_a_distribuir');

        // Vistas basadas en folio_linea (ya existe)
        DB::statement(<<<SQL
CREATE OR REPLACE VIEW vw_folio_a_distribuir AS
SELECT fl.id_folio, COALESCE(SUM(fl.monto), 0) AS a_distribuir
FROM folio_linea fl
GROUP BY fl.id_folio;
SQL);

        DB::statement(<<<SQL
CREATE OR REPLACE VIEW vw_folio_distribuido AS
SELECT fl.id_folio, COALESCE(SUM(fl.monto), 0) AS distribuido
FROM folio_linea fl
WHERE fl.id_cliente IS NOT NULL
GROUP BY fl.id_folio;
SQL);

        DB::statement(<<<SQL
CREATE OR REPLACE VIEW vw_folio_por_persona AS
SELECT fl.id_folio, fl.id_cliente, COALESCE(SUM(fl.monto), 0) AS asignado
FROM folio_linea fl
WHERE fl.id_cliente IS NOT NULL
GROUP BY fl.id_folio, fl.id_cliente;
SQL);

        // Pagos: condicional según columnas reales
        $hasTP      = Schema::hasTable('transaccion_pago');
        $hasTpCli   = $hasTP && Schema::hasColumn('transaccion_pago', 'id_cliente');
        $hasTpMonto = $hasTP && Schema::hasColumn('transaccion_pago', 'monto');

        if ($hasTP) {
            if ($hasTpCli && $hasTpMonto) {
                DB::statement(<<<SQL
CREATE OR REPLACE VIEW vw_pagos_por_persona AS
SELECT tp.id_folio, tp.id_cliente, COALESCE(SUM(tp.monto), 0) AS pagos
FROM transaccion_pago tp
WHERE tp.id_cliente IS NOT NULL
GROUP BY tp.id_folio, tp.id_cliente;
SQL);

                DB::statement(<<<SQL
CREATE OR REPLACE VIEW vw_pagos_generales AS
SELECT tp.id_folio, COALESCE(SUM(tp.monto), 0) AS pagos_generales
FROM transaccion_pago tp
WHERE tp.id_cliente IS NULL
GROUP BY tp.id_folio;
SQL);
            } else {
                // sin id_cliente o monto
                DB::statement("CREATE OR REPLACE VIEW vw_pagos_por_persona AS SELECT 0 AS id_folio, 0 AS id_cliente, 0.00 AS pagos LIMIT 0");

                if ($hasTpMonto) {
                    DB::statement(<<<SQL
CREATE OR REPLACE VIEW vw_pagos_generales AS
SELECT tp.id_folio, COALESCE(SUM(tp.monto), 0) AS pagos_generales
FROM transaccion_pago tp
GROUP BY tp.id_folio;
SQL);
                } else {
                    DB::statement("CREATE OR REPLACE VIEW vw_pagos_generales AS SELECT 0 AS id_folio, 0.00 AS pagos_generales LIMIT 0");
                }
            }
        } else {
            // no hay tabla de pagos
            DB::statement("CREATE OR REPLACE VIEW vw_pagos_por_persona AS SELECT 0 AS id_folio, 0 AS id_cliente, 0.00 AS pagos LIMIT 0");
            DB::statement("CREATE OR REPLACE VIEW vw_pagos_generales AS SELECT 0 AS id_folio, 0.00 AS pagos_generales LIMIT 0");
        }

        // Resumen: usa 'folio' (singular)
        if (Schema::hasTable('folio') && Schema::hasColumn('folio', 'id_folio')) {
            DB::statement(<<<SQL
CREATE OR REPLACE VIEW vw_folio_resumen AS
SELECT
  f.id_folio,
  COALESCE(a.a_distribuir, 0)        AS a_distribuir,
  COALESCE(d.distribuido, 0)         AS distribuido,
  COALESCE(cg.cargos_sin_persona,0)  AS cargos_sin_persona,
  COALESCE(pg.pagos_generales,0)     AS pagos_generales
FROM folio f
LEFT JOIN vw_folio_a_distribuir a ON a.id_folio = f.id_folio
LEFT JOIN vw_folio_distribuido d  ON d.id_folio = f.id_folio
LEFT JOIN (
  SELECT fl.id_folio, COALESCE(SUM(fl.monto), 0) AS cargos_sin_persona
  FROM folio_linea fl
  WHERE fl.id_cliente IS NULL
  GROUP BY fl.id_folio
) cg ON cg.id_folio = f.id_folio
LEFT JOIN vw_pagos_generales pg ON pg.id_folio = f.id_folio;
SQL);
        } else {
            // Fallback seguro si la tabla aún no está en esta BD
            DB::statement("CREATE OR REPLACE VIEW vw_folio_resumen AS SELECT 0 AS id_folio, 0.00 AS a_distribuir, 0.00 AS distribuido, 0.00 AS cargos_sin_persona, 0.00 AS pagos_generales LIMIT 0");
        }
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vw_folio_resumen');
        DB::statement('DROP VIEW IF EXISTS vw_pagos_generales');
        DB::statement('DROP VIEW IF EXISTS vw_cargos_generales');
        DB::statement('DROP VIEW IF EXISTS vw_pagos_por_persona');
        DB::statement('DROP VIEW IF EXISTS vw_folio_por_persona');
        DB::statement('DROP VIEW IF EXISTS vw_folio_distribuido');
        DB::statement('DROP VIEW IF EXISTS vw_folio_a_distribuir');
    }
};
