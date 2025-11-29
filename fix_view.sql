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
  WHERE fl.id_cliente IS NULL AND fl.monto > 0
  GROUP BY fl.id_folio
) cg ON cg.id_folio = f.id_folio
LEFT JOIN vw_pagos_generales pg ON pg.id_folio = f.id_folio;
