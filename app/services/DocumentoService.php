<?php
/**
 * DocumentoService — Operaciones sobre documentos.
 *
 * Esta versión alinea el servicio con migration 010:
 *   - Usa `documentos.raiz_id` (denormalizado) en lugar de CTE recursivo.
 *   - El trigger `trg_documentos_bi` deriva raiz_id en el INSERT.
 *   - El trigger `trg_documentos_ai_root` desactiva la versión activa previa
 *     del mismo árbol, manteniendo el unique parcial `uq_doc_version_activa_por_raiz`.
 *   - Para evitar la race "dos versiones nuevas al mismo tiempo" se toma
 *     `SELECT ... FOR UPDATE` sobre el padre dentro de la transacción.
 *   - `registrarValidacion()` implementa vigencia: una sola validación vigente
 *     por documento (unique parcial `uq_validacion_vigente_por_doc`).
 */

declare(strict_types=1);

final class DocumentoService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Registra un documento nuevo. Si `padre_documento_id` se provee,
     * crea una nueva versión del mismo árbol con lock pesimista sobre el padre.
     *
     * @param array{
     *     contribuyente_id:int, tipo_documento_id:int,
     *     padre_documento_id?:?int,
     *     nombre_original?:?string, nombre_archivo:string, ruta_archivo:string,
     *     mime_type?:?string, tamano_bytes?:?int, hash_sha256?:?string,
     *     numero_folio?:?string, fecha_emision?:?string, fecha_vigencia?:?string,
     *     reutilizable?:bool, exclusivo_expediente_id?:?int
     * } $datos
     * @return int ID del documento recién insertado
     */
    public function registrarDocumento(array $datos): int
    {
        $padreId = $datos['padre_documento_id'] ?? null;

        $abrioTx = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $abrioTx = true;
        }

        try {
            $version = 1;
            if ($padreId !== null) {
                // Lock pesimista sobre el padre para serializar versiones concurrentes
                // en el mismo árbol. El trigger BEFORE INSERT hará FOR UPDATE también,
                // pero tomarlo aquí permite calcular version con consistencia.
                $stmtLock = $this->db->prepare('
                    SELECT d.version, d.raiz_id
                    FROM documentos d
                    WHERE d.id = :pid
                    FOR UPDATE
                ');
                $stmtLock->execute([':pid' => $padreId]);
                $padre = $stmtLock->fetch(PDO::FETCH_ASSOC);
                if ($padre === false) {
                    throw new RuntimeException("padre_documento_id {$padreId} no existe");
                }

                // El siguiente MAX(version) del árbol, no de la fila padre, por si el
                // usuario eligió un nodo intermedio como padre (caso raro pero seguro).
                $raiz = $padre['raiz_id'] ?? $padreId;
                $stmtMax = $this->db->prepare('
                    SELECT COALESCE(MAX(version), 0) + 1
                    FROM documentos
                    WHERE raiz_id = :raiz
                ');
                $stmtMax->execute([':raiz' => $raiz]);
                $version = (int)$stmtMax->fetchColumn();
            }

            $stmt = $this->db->prepare('
                INSERT INTO documentos (
                    contribuyente_id, tipo_documento_id, padre_documento_id, version, es_version_activa,
                    nombre_original, nombre_archivo, ruta_archivo, mime_type, tamano_bytes, hash_sha256,
                    numero_folio, fecha_emision, fecha_vigencia, reutilizable, exclusivo_expediente_id,
                    estado_validacion
                ) VALUES (
                    :contrib_id, :tipo_doc_id, :padre_id, :version, TRUE,
                    :nombre_original, :nombre_archivo, :ruta_archivo, :mime_type, :tamano_bytes, :hash,
                    :folio, :fecha_emision, :fecha_vigencia, :reutilizable, :exclusivo_exp_id,
                    \'pendiente\'
                )
                RETURNING id
            ');
            $stmt->execute([
                ':contrib_id'       => $datos['contribuyente_id'],
                ':tipo_doc_id'      => $datos['tipo_documento_id'],
                ':padre_id'         => $padreId,
                ':version'          => $version,
                ':nombre_original'  => $datos['nombre_original'] ?? null,
                ':nombre_archivo'   => $datos['nombre_archivo'],
                ':ruta_archivo'     => $datos['ruta_archivo'],
                ':mime_type'        => $datos['mime_type'] ?? null,
                ':tamano_bytes'     => $datos['tamano_bytes'] ?? null,
                ':hash'             => $datos['hash_sha256'] ?? null,
                ':folio'            => $datos['numero_folio'] ?? null,
                ':fecha_emision'    => $datos['fecha_emision'] ?? null,
                ':fecha_vigencia'   => $datos['fecha_vigencia'] ?? null,
                ':reutilizable'     => array_key_exists('reutilizable', $datos)
                                        ? ($datos['reutilizable'] ? 'true' : 'false')
                                        : 'true',
                ':exclusivo_exp_id' => $datos['exclusivo_expediente_id'] ?? null,
            ]);
            $documentoId = (int)$stmt->fetchColumn();

            if ($abrioTx) {
                $this->db->commit();
            }
            return $documentoId;
        } catch (Throwable $e) {
            if ($abrioTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Vincula un documento a un expediente/trámite con contexto forense.
     *
     * @param int         $documentoId
     * @param int         $expedienteId
     * @param int|null    $tramiteId
     * @param string      $uso
     * @param bool        $reutilizado            Si el documento ya existía y se reutiliza
     * @param int|null    $desdeExpedienteId      Origen del reuso (si aplica)
     * @param string|null $justificacion
     */
    public function vincularDocumentoExpediente(
        int $documentoId,
        int $expedienteId,
        ?int $tramiteId = null,
        string $uso = 'requisito',
        bool $reutilizado = false,
        ?int $desdeExpedienteId = null,
        ?string $justificacion = null
    ): bool {
        $usuarioId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
        $correlationId = function_exists('correlationId') ? correlationId() : null;

        $stmt = $this->db->prepare('
            INSERT INTO documento_expediente (
                documento_id, expediente_id, tramite_id, uso, reutilizado,
                reutilizado_por_usuario_id, reutilizado_desde_expediente_id,
                reutilizado_at, justificacion, correlation_id
            )
            VALUES (
                :doc_id, :exp_id, :tramite_id, :uso, :reutilizado,
                :reuti_user, :reuti_from,
                CASE WHEN :reutilizado2 THEN NOW() ELSE NULL END,
                :justificacion, :correlation_id
            )
            ON CONFLICT (documento_id, expediente_id, tramite_id, uso) DO UPDATE SET
                reutilizado                      = EXCLUDED.reutilizado OR documento_expediente.reutilizado,
                reutilizado_por_usuario_id       = COALESCE(EXCLUDED.reutilizado_por_usuario_id, documento_expediente.reutilizado_por_usuario_id),
                reutilizado_desde_expediente_id  = COALESCE(EXCLUDED.reutilizado_desde_expediente_id, documento_expediente.reutilizado_desde_expediente_id),
                reutilizado_at                   = COALESCE(EXCLUDED.reutilizado_at, documento_expediente.reutilizado_at),
                justificacion                    = COALESCE(EXCLUDED.justificacion, documento_expediente.justificacion),
                correlation_id                   = COALESCE(EXCLUDED.correlation_id, documento_expediente.correlation_id)
        ');
        $stmt->execute([
            ':doc_id'          => $documentoId,
            ':exp_id'          => $expedienteId,
            ':tramite_id'      => $tramiteId,
            ':uso'             => $uso,
            ':reutilizado'     => $reutilizado ? 'true' : 'false',
            ':reutilizado2'    => $reutilizado ? 'true' : 'false',
            ':reuti_user'      => $reutilizado ? $usuarioId : null,
            ':reuti_from'      => $reutilizado ? $desdeExpedienteId : null,
            ':justificacion'   => $justificacion,
            ':correlation_id'  => $correlationId,
        ]);
        return $stmt->rowCount() >= 0;
    }

    /**
     * Conveniencia para casos de reutilización.
     */
    public function reutilizarDocumento(
        int $documentoId,
        int $expedienteId,
        ?int $tramiteId = null,
        ?int $desdeExpedienteId = null,
        ?string $justificacion = null
    ): bool {
        return $this->vincularDocumentoExpediente(
            $documentoId,
            $expedienteId,
            $tramiteId,
            'requisito',
            true,
            $desdeExpedienteId,
            $justificacion
        );
    }

    /**
     * Documento activo más reciente del contribuyente para un tipo dado.
     * @return list<array<string,mixed>>
     */
    public function getDocumentosPorTipo(int $contribuyenteId, int $tipoDocumentoId): array
    {
        $stmt = $this->db->prepare('
            SELECT d.*, td.nombre AS tipo_nombre, td.requiere_vigencia
            FROM documentos d
            JOIN tipos_documento td ON td.id = d.tipo_documento_id
            WHERE d.contribuyente_id   = :contrib_id
              AND d.tipo_documento_id  = :tipo_id
              AND d.es_version_activa  = TRUE
            ORDER BY d.created_at DESC
            LIMIT 1
        ');
        $stmt->execute([':contrib_id' => $contribuyenteId, ':tipo_id' => $tipoDocumentoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Todos los documentos activos del contribuyente.
     * @return list<array<string,mixed>>
     */
    public function getDocumentosContribuyente(int $contribuyenteId): array
    {
        $stmt = $this->db->prepare('
            SELECT d.*, td.nombre AS tipo_nombre, td.clave AS tipo_clave, td.requiere_vigencia
            FROM documentos d
            JOIN tipos_documento td ON td.id = d.tipo_documento_id
            WHERE d.contribuyente_id = :contrib_id
              AND d.es_version_activa = TRUE
            ORDER BY td.nombre ASC, d.created_at DESC
        ');
        $stmt->execute([':contrib_id' => $contribuyenteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Árbol completo de versiones de un documento usando raiz_id denormalizado.
     * @return list<array<string,mixed>>
     */
    public function getVersiones(int $documentoId): array
    {
        $stmt = $this->db->prepare('
            WITH base AS (
                SELECT COALESCE(raiz_id, id) AS raiz
                FROM documentos
                WHERE id = :doc_id
            )
            SELECT d.*, td.nombre AS tipo_nombre
            FROM documentos d
            JOIN tipos_documento td ON td.id = d.tipo_documento_id
            JOIN base b ON d.raiz_id = b.raiz OR d.id = b.raiz
            ORDER BY d.version ASC, d.id ASC
        ');
        $stmt->execute([':doc_id' => $documentoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Documentos pendientes de validación (vista del validador).
     * @return list<array<string,mixed>>
     */
    public function getDocumentosPendientes(): array
    {
        $stmt = $this->db->prepare('
            SELECT d.*, td.nombre AS tipo_nombre, td.clave AS tipo_clave,
                   c.nombre_completo AS contribuyente_nombre, c.rfc
            FROM documentos d
            JOIN tipos_documento td ON td.id = d.tipo_documento_id
            JOIN contribuyentes   c  ON c.id  = d.contribuyente_id
            WHERE d.estado_validacion = \'pendiente\'
              AND d.es_version_activa = TRUE
            ORDER BY d.created_at ASC
        ');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Registra una validación respetando la semántica de `vigente`:
     *   - Solo una validación vigente por documento (unique parcial).
     *   - Si ya había una vigente y esta nueva la reemplaza (rectificación),
     *     la anterior se marca como vigente=FALSE, invalidada_por_id=NEW,
     *     motivo_rectificacion y estado_anterior quedan registrados en la NUEVA.
     *   - Actualiza documentos.estado_validacion y documentos.validacion_vigente_id.
     *
     * @param int         $documentoId
     * @param int         $validadorId
     * @param string      $resultado              Debe coincidir con el enum `estado_validacion`
     * @param string|null $observaciones
     * @param string|null $motivoRectificacion    Requerido si reemplaza una validación previa distinta
     * @return int ID de la nueva validación (NO boolean; 0 si falla)
     */
    public function registrarValidacion(
        int $documentoId,
        int $validadorId,
        string $resultado,
        ?string $observaciones = null,
        ?string $motivoRectificacion = null
    ): int {
        $correlationId = function_exists('correlationId') ? correlationId() : null;

        $abrioTx = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $abrioTx = true;
        }

        try {
            // Lock del documento y busqueda de vigente previa en un solo roundtrip.
            $stmtDoc = $this->db->prepare('
                SELECT id, estado_validacion, validacion_vigente_id
                FROM documentos
                WHERE id = :id
                FOR UPDATE
            ');
            $stmtDoc->execute([':id' => $documentoId]);
            $doc = $stmtDoc->fetch(PDO::FETCH_ASSOC);
            if ($doc === false) {
                throw new RuntimeException("documento {$documentoId} no existe");
            }

            $estadoAnterior = $doc['estado_validacion'] ?? null;

            // Vigente previa (si existe)
            $stmtVig = $this->db->prepare('
                SELECT id, resultado
                FROM validaciones_documento
                WHERE documento_id = :doc_id AND vigente = TRUE
                FOR UPDATE
            ');
            $stmtVig->execute([':doc_id' => $documentoId]);
            $previa = $stmtVig->fetch(PDO::FETCH_ASSOC);

            // Si hay vigente previa y se cambia el resultado, exigir motivo.
            if ($previa !== false && $motivoRectificacion === null && $previa['resultado'] !== $resultado) {
                throw new InvalidArgumentException(
                    'Se requiere motivo_rectificacion para reemplazar una validación vigente previa.'
                );
            }

            // Desactivar vigente previa (si existe) ANTES de insertar la nueva,
            // para no violar el unique parcial `uq_validacion_vigente_por_doc`.
            if ($previa !== false) {
                $stmtPrev = $this->db->prepare('
                    UPDATE validaciones_documento
                    SET vigente = FALSE
                    WHERE id = :id
                ');
                $stmtPrev->execute([':id' => $previa['id']]);
            }

            // Insertar la nueva validación vigente.
            $stmtIns = $this->db->prepare('
                INSERT INTO validaciones_documento (
                    documento_id, usuario_validador_id, resultado, observaciones,
                    vigente, invalidada_por_id, motivo_rectificacion, estado_anterior, correlation_id
                ) VALUES (
                    :doc_id, :validador_id, :resultado, :obs,
                    TRUE, NULL, :motivo_rect, :estado_anterior, :correlation_id
                )
                RETURNING id
            ');
            $stmtIns->execute([
                ':doc_id'          => $documentoId,
                ':validador_id'    => $validadorId,
                ':resultado'       => $resultado,
                ':obs'             => $observaciones,
                ':motivo_rect'     => $motivoRectificacion,
                ':estado_anterior' => $estadoAnterior,
                ':correlation_id'  => $correlationId,
            ]);
            $nuevaId = (int)$stmtIns->fetchColumn();

            // Enlazar hacia atrás: invalidada_por_id en la previa.
            if ($previa !== false) {
                $stmtLink = $this->db->prepare('
                    UPDATE validaciones_documento
                    SET invalidada_por_id = :nueva
                    WHERE id = :id
                ');
                $stmtLink->execute([':nueva' => $nuevaId, ':id' => $previa['id']]);
            }

            // Actualizar documento: estado y puntero a vigente.
            $stmtUpd = $this->db->prepare('
                UPDATE documentos
                SET estado_validacion     = :resultado,
                    validacion_vigente_id = :vig_id
                WHERE id = :id
            ');
            $stmtUpd->execute([
                ':resultado' => $resultado,
                ':vig_id'    => $nuevaId,
                ':id'        => $documentoId,
            ]);

            if ($abrioTx) {
                $this->db->commit();
            }
            return $nuevaId;
        } catch (Throwable $e) {
            if ($abrioTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
