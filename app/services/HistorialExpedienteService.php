<?php
/**
 * HistorialExpedienteService — Registra eventos del ciclo de vida de un expediente.
 *
 * Fuente de eventos válidos: tabla `historial_eventos_catalogo` (migration 010).
 * Las constantes de clase son un espejo en PHP para evitar "stringly typed code".
 *
 * Al registrar un evento:
 *   - Se verifica (cache en memoria) que el evento exista en el catálogo.
 *   - La categoría se resuelve automáticamente desde el catálogo — no se recibe del caller.
 *   - Se capturan usuario_id, ip y correlation_id del request.
 *   - metadatos es un JSONB opcional con contexto estructurado del evento.
 */

declare(strict_types=1);

final class HistorialExpedienteService
{
    private PDO $db;

    /** Cache en memoria del catálogo. Evita un roundtrip por cada registrar(). */
    private ?array $catalogo = null;

    // ─── Constantes espejo del catálogo (historial_eventos_catalogo) ──────

    // EXPEDIENTE
    public const EV_CREACION_EXP          = 'CREACION_EXP';
    public const EV_CAMBIO_ESTADO_EXP     = 'CAMBIO_ESTADO_EXP';
    public const EV_OBS_EDITADA_EXP       = 'OBS_EDITADA_EXP';
    public const EV_CAMBIO_PREDIO_EXP     = 'CAMBIO_PREDIO_EXP';
    public const EV_EXP_ARCHIVADO         = 'EXP_ARCHIVADO';

    // TRAMITE
    public const EV_NUEVO_TRAMITE         = 'NUEVO_TRAMITE';
    public const EV_CAMBIO_ESTADO_TRAMITE = 'CAMBIO_ESTADO_TRAMITE';
    public const EV_TRAMITE_ENVIADO       = 'TRAMITE_ENVIADO';
    public const EV_TRAMITE_DICTAMINADO   = 'TRAMITE_DICTAMINADO';
    public const EV_DEP_AGREGADA          = 'DEP_AGREGADA';
    public const EV_DEP_ELIMINADA         = 'DEP_ELIMINADA';

    // DOCUMENTO
    public const EV_DOC_SUBIDO            = 'DOC_SUBIDO';
    public const EV_DOC_REUTILIZADO       = 'DOC_REUTILIZADO';
    public const EV_DOC_NUEVA_VERSION     = 'DOC_NUEVA_VERSION';
    public const EV_DOC_APROBADO          = 'DOC_APROBADO';
    public const EV_DOC_RECHAZADO         = 'DOC_RECHAZADO';
    public const EV_DOC_EN_REVISION       = 'DOC_EN_REVISION';
    public const EV_DOC_RECTIFICADO       = 'DOC_RECTIFICADO';
    public const EV_DOC_VENCIDO           = 'DOC_VENCIDO';

    // INSPECCION
    public const EV_INSPECCION_PROGRAMADA = 'INSPECCION_PROGRAMADA';
    public const EV_INSPECCION_REALIZADA  = 'INSPECCION_REALIZADA';

    // FISCAL
    public const EV_PAGO_APLICADO_EXP     = 'PAGO_APLICADO_EXP';
    public const EV_PAGO_REVERSADO        = 'PAGO_REVERSADO';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Registra un evento del expediente. La categoría se resuelve desde el catálogo.
     *
     * @param int                 $expedienteId
     * @param string              $evento       Una de las constantes self::EV_*
     * @param string|null         $descripcion  Texto libre (opcional)
     * @param array<string,mixed> $metadatos    Contexto estructurado (opcional)
     * @return int ID del registro en historial_expediente
     */
    public function registrar(
        int $expedienteId,
        string $evento,
        ?string $descripcion = null,
        array $metadatos = []
    ): int {
        $categoria = $this->categoriaDeEvento($evento);
        if ($categoria === null) {
            throw new InvalidArgumentException(
                "HistorialExpedienteService: evento desconocido '{$evento}'. " .
                "Agrega al catálogo historial_eventos_catalogo antes de emitirlo."
            );
        }

        $ctx = $this->contextoRequest();

        $stmt = $this->db->prepare('
            INSERT INTO historial_expediente
                (expediente_id, evento, categoria, descripcion, usuario_id,
                 metadatos, correlation_id, ip)
            VALUES
                (:expediente_id, :evento, :categoria, :descripcion, :usuario_id,
                 CAST(:metadatos AS JSONB), :correlation_id, :ip)
            RETURNING id
        ');
        $stmt->execute([
            ':expediente_id'  => $expedienteId,
            ':evento'         => $evento,
            ':categoria'      => $categoria,
            ':descripcion'    => $descripcion,
            ':usuario_id'     => $ctx['usuario_id'],
            ':metadatos'      => $metadatos === [] ? null : $this->toJson($metadatos),
            ':correlation_id' => $ctx['correlation_id'],
            ':ip'             => $ctx['ip'],
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Lista eventos de un expediente en orden cronológico inverso.
     *
     * @return list<array<string,mixed>>
     */
    public function listar(int $expedienteId, int $limit = 200): array
    {
        $stmt = $this->db->prepare('
            SELECT he.id, he.fecha, he.evento, he.categoria, he.descripcion,
                   he.metadatos, he.correlation_id, he.ip,
                   he.usuario_id, u.nombre_completo AS usuario_nombre
            FROM historial_expediente he
            LEFT JOIN usuarios u ON u.id = he.usuario_id
            WHERE he.expediente_id = :expediente_id
            ORDER BY he.fecha DESC, he.id DESC
            LIMIT :limit
        ');
        $stmt->bindValue(':expediente_id', $expedienteId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', max(1, min($limit, 1000)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // ─── Helpers privados ──────────────────────────────────────────────────

    /** @return string|null Categoría del evento, o null si no existe en el catálogo. */
    private function categoriaDeEvento(string $evento): ?string
    {
        if ($this->catalogo === null) {
            $this->catalogo = [];
            $stmt = $this->db->query('
                SELECT evento, categoria
                FROM historial_eventos_catalogo
                WHERE activo = TRUE
            ');
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $this->catalogo[$row['evento']] = $row['categoria'];
            }
        }
        return $this->catalogo[$evento] ?? null;
    }

    /** @return array{usuario_id:?int,ip:?string,correlation_id:?string} */
    private function contextoRequest(): array
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        return [
            'usuario_id'     => isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null,
            'ip'             => is_string($ip) && $ip !== '' ? substr($ip, 0, 45) : null,
            'correlation_id' => function_exists('correlationId') ? correlationId() : null,
        ];
    }

    private function toJson(array $v): string
    {
        $json = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        return $json === false ? '{}' : $json;
    }
}
