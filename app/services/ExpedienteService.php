<?php
/**
 * ExpedienteService — Gestión de expedientes y trámites
 */

class ExpedienteService {

    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Genera un número de expediente único
     */
    public function generarNumeroExpediente(): string {
        $anio = date('Y');
        $stmt = $this->db->query("SELECT nextval('seq_expediente_num')");
        $consecutivo = (int)$stmt->fetchColumn();
        return 'EXP-' . $anio . '-' . str_pad($consecutivo, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Genera un número de trámite único
     */
    public function generarNumeroTramite(): string {
        $anio = date('Y');
        $stmt = $this->db->query("SELECT nextval('seq_tramite_num')");
        $consecutivo = (int)$stmt->fetchColumn();
        return 'TRM-' . $anio . '-' . str_pad($consecutivo, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Crea un expediente y un trámite en una transacción
     */
    public function crearExpedienteConTramite(array $datosExp, array $datosTramite, int $usuarioId): array {
        $this->db->beginTransaction();
        try {
            $numExp = $this->generarNumeroExpediente();
            $stmtExp = $this->db->prepare('
                INSERT INTO expedientes (numero_expediente, contribuyente_id, predio_id, estado, descripcion)
                VALUES (:num, :contrib_id, :predio_id, \'abierto\', :desc)
                RETURNING id
            ');
            $stmtExp->execute([
                ':num'       => $numExp,
                ':contrib_id'=> $datosExp['contribuyente_id'],
                ':predio_id' => $datosExp['predio_id'] ?? null,
                ':desc'      => $datosExp['descripcion'] ?? null,
            ]);
            $expedienteId = (int)$stmtExp->fetchColumn();

            // Historial
            $this->registrarHistorial($expedienteId, 'CREACION', 'Expediente creado: ' . $numExp, $usuarioId);

            $numTram = $this->generarNumeroTramite();
            $stmtTram = $this->db->prepare('
                INSERT INTO tramites (numero_tramite, expediente_id, tipo_tramite_id, estado, observaciones)
                VALUES (:num, :exp_id, :tipo_id, \'iniciado\', :obs)
                RETURNING id
            ');
            $stmtTram->execute([
                ':num'     => $numTram,
                ':exp_id'  => $expedienteId,
                ':tipo_id' => $datosTramite['tipo_tramite_id'],
                ':obs'     => $datosTramite['observaciones'] ?? null,
            ]);
            $tramiteId = (int)$stmtTram->fetchColumn();

            $this->db->commit();

            return [
                'expediente_id'  => $expedienteId,
                'tramite_id'     => $tramiteId,
                'numero_expediente' => $numExp,
                'numero_tramite'    => $numTram,
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Registra entrada en historial_expediente
     */
    public function registrarHistorial(int $expedienteId, string $evento, string $descripcion, ?int $usuarioId = null): void {
        $stmt = $this->db->prepare('
            INSERT INTO historial_expediente (expediente_id, evento, descripcion, usuario_id)
            VALUES (:exp_id, :evento, :desc, :user_id)
        ');
        $stmt->execute([
            ':exp_id'  => $expedienteId,
            ':evento'  => $evento,
            ':desc'    => $descripcion,
            ':user_id' => $usuarioId,
        ]);
    }

    /**
     * Obtiene expedientes de un contribuyente
     */
    public function getExpedientesContribuyente(int $contribuyenteId): array {
        $stmt = $this->db->prepare('
            SELECT e.*, p.clave_catastral, p.calle, p.numero_exterior, p.colonia,
                   COUNT(t.id) AS total_tramites,
                   COUNT(CASE WHEN t.estado = \'aprobado\' THEN 1 END) AS tramites_aprobados
            FROM expedientes e
            LEFT JOIN predios p ON p.id = e.predio_id
            LEFT JOIN tramites t ON t.expediente_id = e.id
            WHERE e.contribuyente_id = :contrib_id
            GROUP BY e.id, p.clave_catastral, p.calle, p.numero_exterior, p.colonia
            ORDER BY e.created_at DESC
        ');
        $stmt->execute([':contrib_id' => $contribuyenteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un expediente con sus trámites
     */
    public function getExpedienteDetalle(int $expedienteId, int $contribuyenteId): ?array {
        $stmt = $this->db->prepare('
            SELECT e.*, p.clave_catastral, p.calle, p.numero_exterior, p.colonia, p.municipio
            FROM expedientes e
            LEFT JOIN predios p ON p.id = e.predio_id
            WHERE e.id = :id AND e.contribuyente_id = :contrib_id
        ');
        $stmt->execute([':id' => $expedienteId, ':contrib_id' => $contribuyenteId]);
        $expediente = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$expediente) return null;

        $stmtT = $this->db->prepare('
            SELECT t.*, tt.nombre AS tipo_tramite_nombre, tt.clave AS tipo_tramite_clave
            FROM tramites t
            JOIN tipos_tramite tt ON tt.id = t.tipo_tramite_id
            WHERE t.expediente_id = :exp_id
            ORDER BY t.created_at ASC
        ');
        $stmtT->execute([':exp_id' => $expedienteId]);
        $expediente['tramites'] = $stmtT->fetchAll(PDO::FETCH_ASSOC);

        return $expediente;
    }

    /**
     * Cambiar estado de un expediente
     */
    public function cambiarEstadoExpediente(int $expedienteId, string $nuevoEstado, ?int $usuarioId = null): bool {
        $stmt = $this->db->prepare('UPDATE expedientes SET estado = :estado WHERE id = :id');
        $stmt->execute([':estado' => $nuevoEstado, ':id' => $expedienteId]);
        if ($stmt->rowCount() > 0) {
            $this->registrarHistorial($expedienteId, 'CAMBIO_ESTADO', 'Estado actualizado a: ' . $nuevoEstado, $usuarioId);
        }
        return $stmt->rowCount() > 0;
    }
}
