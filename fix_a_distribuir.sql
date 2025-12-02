CREATE OR REPLACE VIEW vw_folio_a_distribuir AS
SELECT fl.id_folio, COALESCE(SUM(fl.monto), 0) AS a_distribuir
FROM folio_linea fl
WHERE fl.monto > 0
GROUP BY fl.id_folio;
