<?php
declare(strict_types=1);

/**
 * Paginacion keyset - REMTYS
 *
 * Keyset (cursor) pagination en lugar de OFFSET. Ventajas sobre LIMIT/OFFSET:
 *   - O(log N) por index scan vs O(OFFSET + LIMIT) que empeora linealmente.
 *   - No se corre la pagina cuando se insertan filas durante la navegacion.
 *   - Consistente bajo concurrencia alta.
 *
 * Patron estandar:
 *   - La consulta ordena por (<col_secundaria>, id) DESC.
 *   - El "cursor" es el par (valor_col_secundaria, id) del ultimo registro visto.
 *   - Para obtener la siguiente pagina: WHERE (col, id) < (cursor_col, cursor_id).
 *
 * Uso canonico:
 *   $cursor = cursorDesdeRequest();                       // parse seguro del cursor
 *   $pagina = keysetFetch(
 *       $db,
 *       'SELECT c.id, c.nombre_completo, c.created_at FROM contribuyentes c',
 *       [],
 *       'c.created_at', 'c.id',   // columnas del keyset (order by)
 *       $cursor,
 *       25                         // tamano de pagina
 *   );
 *   // $pagina['items']       → filas
 *   // $pagina['next_cursor'] → string|null para la siguiente pagina
 */

/**
 * Extrae de $_GET['cursor'] un tupla [col_valor, id] validada.
 * Formato esperado: base64url("<col_valor>|<id>") con id entero.
 */
function cursorDesdeRequest(string $paramName = 'cursor'): ?array {
    $raw = $_GET[$paramName] ?? null;
    if ($raw === null || $raw === '') {
        return null;
    }
    $decoded = base64_decode(strtr((string)$raw, '-_', '+/'), true);
    if ($decoded === false) {
        return null;
    }
    $parts = explode('|', $decoded, 2);
    if (count($parts) !== 2) {
        return null;
    }
    $valor = $parts[0];
    $id = filter_var($parts[1], FILTER_VALIDATE_INT);
    if ($id === false) {
        return null;
    }
    return [$valor, (int)$id];
}

function cursorCodificar(string $valorCol, int $id): string {
    return rtrim(strtr(base64_encode($valorCol . '|' . $id), '+/', '-_'), '=');
}

/**
 * Ejecuta una consulta paginada por keyset descendente.
 *
 * @param PDO    $db             Conexion.
 * @param string $sqlBase        SELECT sin ORDER BY ni WHERE de cursor ni LIMIT.
 * @param array  $params         Parametros ya vinculados al SELECT base.
 * @param string $colOrden       Columna secundaria con alias de tabla (ej. 'c.created_at').
 * @param string $colId          Columna id con alias (ej. 'c.id').
 * @param ?array $cursor         [valor_col, id] o null para primera pagina.
 * @param int    $tamPagina      Filas por pagina.
 * @param string $alias          Alias WHERE/AND auto: si $sqlBase ya tiene WHERE pasa 'AND', si no 'WHERE'.
 * @return array{items:array<int,array<string,mixed>>, next_cursor: ?string}
 */
function keysetFetch(
    PDO    $db,
    string $sqlBase,
    array  $params,
    string $colOrden,
    string $colId,
    ?array $cursor,
    int    $tamPagina = 25,
    string $alias = 'AND'
): array {
    $tamPagina = max(1, min(500, $tamPagina));

    $sql = $sqlBase;
    if ($cursor !== null) {
        // Tupla: (col, id) < (:cur_col, :cur_id) para orden DESC.
        $sql .= " $alias ($colOrden, $colId) < (:keyset_col, :keyset_id)";
        $params[':keyset_col'] = $cursor[0];
        $params[':keyset_id']  = $cursor[1];
    }
    $sql .= " ORDER BY $colOrden DESC, $colId DESC LIMIT " . ($tamPagina + 1);

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hayMas = count($filas) > $tamPagina;
    if ($hayMas) {
        $filas = array_slice($filas, 0, $tamPagina);
    }

    $nextCursor = null;
    if ($hayMas && !empty($filas)) {
        $ultima = end($filas);
        $colSimple = preg_replace('/^.+\./', '', $colOrden);
        $idSimple  = preg_replace('/^.+\./', '', $colId);
        $valorCursor = (string)($ultima[$colSimple] ?? '');
        $idCursor    = (int)($ultima[$idSimple] ?? 0);
        if ($idCursor > 0) {
            $nextCursor = cursorCodificar($valorCursor, $idCursor);
        }
    }

    return [
        'items'       => $filas,
        'next_cursor' => $nextCursor,
    ];
}

/**
 * Construye una URL preservando query params actuales y reemplazando 'cursor'.
 */
function urlPaginacion(string $cursorValor, array $excluir = []): string {
    $query = $_GET;
    foreach ($excluir as $k) {
        unset($query[$k]);
    }
    $query['cursor'] = $cursorValor;
    return BASE_URL . '/?' . http_build_query($query);
}
