<?php
/**
 * FolioService — Numeración oficial con lock pesimista.
 *
 * Reemplaza los mecanismos previos (SELECT MAX+1 y secuencias) que sufrían
 * race conditions y podían generar duplicados o huecos no auditables.
 *
 * Tablas consumidas (migration 010):
 *   - folio_contador   (anio, tipo, ultimo_valor)   ← fila bloqueable FOR UPDATE
 *   - folio_oficial    (anio, tipo, consecutivo, numero_completo, entidad_*, estado)
 *
 * Contrato:
 *   - reservarFolio() DEBE llamarse dentro de una transacción abierta por el
 *     caller (ej. al crear el expediente/trámite en la misma transacción).
 *     Si no hay transacción, el servicio abre una y hace commit al terminar.
 *   - El número nunca se reutiliza, incluso si la entidad se anula más tarde.
 *   - ANULADO queda como evento forense en folio_oficial, no se borra.
 */

declare(strict_types=1);

final class FolioService
{
    private PDO $db;

    /** Tipos válidos de folio. Ampliar en migración al agregar nuevos. */
    private const TIPOS_VALIDOS = [
        'EXP', // expedientes
        'TRM', // tramites
        'ENT', // libro de entradas
        'INS', // inspecciones
        'RES', // resoluciones
    ];

    /** Ancho mínimo del consecutivo en numero_completo (zero-pad). */
    private const ANCHO_CONSECUTIVO = 6;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Reserva el siguiente folio oficial para (anio, tipo) con lock pesimista.
     *
     * @param string      $tipo         Tipo de folio — ver TIPOS_VALIDOS
     * @param string|null $entidadTipo  Nombre de la tabla/entidad a la que se asigna (ej. 'expedientes')
     * @param int|null    $entidadId    PK de la entidad (puede ser null si se asigna pre-INSERT)
     * @param int|null    $anio         Año del folio. Default: año actual (timezone America/Mexico_City).
     *
     * @return array{
     *     folio_oficial_id:int,
     *     anio:int,
     *     tipo:string,
     *     consecutivo:int,
     *     numero_completo:string
     * }
     */
    public function reservarFolio(
        string $tipo,
        ?string $entidadTipo = null,
        ?int $entidadId = null,
        ?int $anio = null
    ): array {
        $tipo = strtoupper(trim($tipo));
        if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
            throw new InvalidArgumentException("Tipo de folio no soportado: {$tipo}");
        }
        if ($anio === null) {
            $anio = (int)(new DateTimeImmutable('now', new DateTimeZone('America/Mexico_City')))->format('Y');
        }
        if ($anio < 2000 || $anio > 2999) {
            throw new InvalidArgumentException("Año fuera de rango: {$anio}");
        }

        $abrioTransaccion = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $abrioTransaccion = true;
        }

        try {
            // 1) Asegurar la fila del contador para (anio, tipo).
            //    ON CONFLICT DO NOTHING no incrementa — la siguiente SELECT FOR UPDATE bloqueará la fila existente.
            $stmtUpsert = $this->db->prepare('
                INSERT INTO folio_contador (anio, tipo, ultimo_valor)
                VALUES (:anio, :tipo, 0)
                ON CONFLICT (anio, tipo) DO NOTHING
            ');
            $stmtUpsert->execute([':anio' => $anio, ':tipo' => $tipo]);

            // 2) Bloquear la fila del contador y obtener valor actual.
            $stmtLock = $this->db->prepare('
                SELECT ultimo_valor
                FROM folio_contador
                WHERE anio = :anio AND tipo = :tipo
                FOR UPDATE
            ');
            $stmtLock->execute([':anio' => $anio, ':tipo' => $tipo]);
            $ultimo = $stmtLock->fetchColumn();
            if ($ultimo === false) {
                throw new RuntimeException("FolioService: no se pudo bloquear contador ({$anio}, {$tipo})");
            }
            $siguiente = (int)$ultimo + 1;

            // 3) Incrementar contador.
            $stmtInc = $this->db->prepare('
                UPDATE folio_contador
                SET ultimo_valor = :nuevo, updated_at = NOW()
                WHERE anio = :anio AND tipo = :tipo
            ');
            $stmtInc->execute([
                ':nuevo' => $siguiente,
                ':anio'  => $anio,
                ':tipo'  => $tipo,
            ]);

            // 4) Componer numero_completo y registrar en libro oficial.
            $numeroCompleto = sprintf(
                '%s-%04d-%0' . self::ANCHO_CONSECUTIVO . 'd',
                $tipo,
                $anio,
                $siguiente
            );

            $ctx = $this->contextoRequest();

            $stmtIns = $this->db->prepare('
                INSERT INTO folio_oficial
                    (anio, tipo, consecutivo, numero_completo,
                     entidad_tipo, entidad_id, estado,
                     usuario_id, correlation_id)
                VALUES
                    (:anio, :tipo, :consecutivo, :numero_completo,
                     :entidad_tipo, :entidad_id, \'ASIGNADO\',
                     :usuario_id, :correlation_id)
                RETURNING id
            ');
            $stmtIns->execute([
                ':anio'            => $anio,
                ':tipo'            => $tipo,
                ':consecutivo'     => $siguiente,
                ':numero_completo' => $numeroCompleto,
                ':entidad_tipo'    => $entidadTipo,
                ':entidad_id'      => $entidadId,
                ':usuario_id'      => $ctx['usuario_id'],
                ':correlation_id'  => $ctx['correlation_id'],
            ]);
            $folioOficialId = (int)$stmtIns->fetchColumn();

            if ($abrioTransaccion) {
                $this->db->commit();
            }

            return [
                'folio_oficial_id' => $folioOficialId,
                'anio'             => $anio,
                'tipo'             => $tipo,
                'consecutivo'      => $siguiente,
                'numero_completo'  => $numeroCompleto,
            ];
        } catch (Throwable $e) {
            if ($abrioTransaccion && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Asocia un folio ya reservado a su entidad recién creada.
     * Útil cuando se reserva el folio antes de tener el PK.
     */
    public function asociarEntidad(int $folioOficialId, string $entidadTipo, int $entidadId): void
    {
        $stmt = $this->db->prepare('
            UPDATE folio_oficial
            SET entidad_tipo = :entidad_tipo,
                entidad_id   = :entidad_id
            WHERE id = :id
              AND estado = \'ASIGNADO\'
              AND entidad_id IS NULL
        ');
        $stmt->execute([
            ':entidad_tipo' => $entidadTipo,
            ':entidad_id'   => $entidadId,
            ':id'           => $folioOficialId,
        ]);
        if ($stmt->rowCount() === 0) {
            throw new RuntimeException("FolioService::asociarEntidad: folio {$folioOficialId} ya asociado, anulado o inexistente");
        }
    }

    /**
     * Anula un folio (nunca se elimina). Deja traza forense en folio_oficial.
     *
     * @return bool true si se anuló, false si ya estaba anulado o no existe.
     */
    public function anularFolio(string $numeroCompleto, string $motivo): bool
    {
        if (trim($motivo) === '') {
            throw new InvalidArgumentException('anularFolio requiere motivo.');
        }

        $ctx = $this->contextoRequest();

        $stmt = $this->db->prepare('
            UPDATE folio_oficial
            SET estado           = \'ANULADO\',
                motivo_anulacion = :motivo,
                anulado_at       = NOW(),
                usuario_id       = COALESCE(:usuario_id, usuario_id),
                correlation_id   = COALESCE(:correlation_id, correlation_id)
            WHERE numero_completo = :numero_completo
              AND estado = \'ASIGNADO\'
        ');
        $stmt->execute([
            ':motivo'          => $motivo,
            ':usuario_id'      => $ctx['usuario_id'],
            ':correlation_id'  => $ctx['correlation_id'],
            ':numero_completo' => $numeroCompleto,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Busca un folio por numero_completo. Devuelve null si no existe.
     *
     * @return array<string,mixed>|null
     */
    public function buscarPorNumero(string $numeroCompleto): ?array
    {
        $stmt = $this->db->prepare('
            SELECT id, anio, tipo, consecutivo, numero_completo,
                   entidad_tipo, entidad_id, estado, motivo_anulacion,
                   usuario_id, correlation_id, generado_at, anulado_at
            FROM folio_oficial
            WHERE numero_completo = :numero_completo
        ');
        $stmt->execute([':numero_completo' => $numeroCompleto]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Obtiene el folio asignado a una entidad, si existe.
     *
     * @return array<string,mixed>|null
     */
    public function buscarPorEntidad(string $entidadTipo, int $entidadId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT id, anio, tipo, consecutivo, numero_completo, estado, generado_at
            FROM folio_oficial
            WHERE entidad_tipo = :entidad_tipo
              AND entidad_id   = :entidad_id
              AND estado = \'ASIGNADO\'
            ORDER BY id DESC
            LIMIT 1
        ');
        $stmt->execute([
            ':entidad_tipo' => $entidadTipo,
            ':entidad_id'   => $entidadId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    // ─── Privados ──────────────────────────────────────────────────────────

    /** @return array{usuario_id:?int,correlation_id:?string} */
    private function contextoRequest(): array
    {
        return [
            'usuario_id'     => isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null,
            'correlation_id' => function_exists('correlationId') ? correlationId() : null,
        ];
    }
}
