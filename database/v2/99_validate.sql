SELECT td.codigo, td.nombre, td.scope,
       count(DISTINCT r.tramite_id) AS aparece_en_tramites
FROM tipo_documento td
JOIN tramite_requisito r ON r.tipo_documento_id = td.tipo_documento_id
GROUP BY td.tipo_documento_id, td.codigo, td.nombre, td.scope
ORDER BY aparece_en_tramites DESC, td.codigo
LIMIT 15;

SELECT g.codigo, g.nombre, count(DISTINCT r.tramite_id) AS en_tramites
FROM grupo_documento g JOIN tramite_requisito r USING(grupo_id)
GROUP BY g.grupo_id, g.codigo, g.nombre;
