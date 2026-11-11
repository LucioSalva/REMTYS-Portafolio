<?php
/**
 * Helpers de contribuyentes
 */

function normalizarDetalleContribuyente(array $source): array {
    return [
        'curp' => sanitizarCURP($source['curp'] ?? ''),
        'representante_legal' => sanitizarTexto($source['representante_legal'] ?? ''),
        'rfc_representante' => sanitizarRFC($source['rfc_representante'] ?? ''),
    ];
}

function validarDetalleContribuyente(string $tipoPersona, array $detalle): array {
    $errores = [];

    if ($tipoPersona === 'fisica') {
        if (!estaVacio($detalle['curp']) && !validarCURP($detalle['curp'])) {
            $errores[] = 'El CURP no tiene un formato valido.';
        }
        return $errores;
    }

    if (estaVacio($detalle['representante_legal']) || !validarLongitudTexto($detalle['representante_legal'], 3, 400)) {
        $errores[] = 'El representante legal es requerido para persona moral.';
    }
    if (estaVacio($detalle['rfc_representante']) || !validarRFC($detalle['rfc_representante'])) {
        $errores[] = 'El RFC del representante legal no tiene un formato valido.';
    }

    return $errores;
}

function guardarDetalleContribuyente(PDO $db, int $contribuyenteId, string $tipoPersona, array $detalle): void {
    if ($tipoPersona === 'fisica') {
        $db->prepare('DELETE FROM contribuyente_moral WHERE contribuyente_id = :id')->execute([':id' => $contribuyenteId]);
        $stmt = $db->prepare(
            'INSERT INTO contribuyente_fisica (contribuyente_id, curp)
             VALUES (:id, :curp)
             ON CONFLICT (contribuyente_id)
             DO UPDATE SET curp = EXCLUDED.curp, updated_at = NOW()'
        );
        $stmt->execute([
            ':id' => $contribuyenteId,
            ':curp' => $detalle['curp'] ?: null,
        ]);
        return;
    }

    $db->prepare('DELETE FROM contribuyente_fisica WHERE contribuyente_id = :id')->execute([':id' => $contribuyenteId]);
    $stmt = $db->prepare(
        'INSERT INTO contribuyente_moral (contribuyente_id, representante_legal, rfc_representante)
         VALUES (:id, :representante_legal, :rfc_representante)
         ON CONFLICT (contribuyente_id)
         DO UPDATE SET representante_legal = EXCLUDED.representante_legal,
                       rfc_representante = EXCLUDED.rfc_representante,
                       updated_at = NOW()'
    );
    $stmt->execute([
        ':id' => $contribuyenteId,
        ':representante_legal' => $detalle['representante_legal'] ?: null,
        ':rfc_representante' => $detalle['rfc_representante'] ?: null,
    ]);
}

function obtenerContribuyenteDetalle(PDO $db, int $contribuyenteId): ?array {
    $stmt = $db->prepare('SELECT * FROM v_contribuyentes_detalle WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $contribuyenteId]);
    $contribuyente = $stmt->fetch(PDO::FETCH_ASSOC);
    return $contribuyente ?: null;
}

function obtenerNombreContribuyente(array $contribuyente): string {
    return trim((string)($contribuyente['nombre_mostrado'] ?? $contribuyente['nombre_completo'] ?? ''));
}
