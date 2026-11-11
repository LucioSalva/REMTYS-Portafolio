<?php
/**
 * FiscalService — Gestión de obligaciones fiscales y pagos
 */

class FiscalService {

    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Obtiene el saldo fiscal de un contribuyente
     */
    public function getSaldoContribuyente(int $contribuyenteId): array {
        $stmt = $this->db->prepare('SELECT * FROM v_saldo_contribuyente WHERE contribuyente_id = :cid');
        $stmt->execute([':cid' => $contribuyenteId]);
        $saldo = $stmt->fetch(PDO::FETCH_ASSOC);
        return $saldo ?: [
            'total_cargo' => 0, 'total_pagado' => 0, 'saldo_pendiente' => 0,
            'obligaciones_pendientes' => 0, 'obligaciones_pagadas' => 0
        ];
    }

    /**
     * Obtiene obligaciones con detalle de periodos
     */
    public function getObligacionesContribuyente(int $contribuyenteId): array {
        $stmt = $this->db->prepare('
            SELECT of.id, of.tipo, of.descripcion, of.monto_base,
                   p.clave_catastral, p.calle, p.numero_exterior,
                   op.id AS periodo_op_id, pf.anio, pf.descripcion AS periodo_desc,
                   op.monto, op.monto_recargos, op.pagado, op.monto_pagado, op.fecha_limite,
                   (op.monto + op.monto_recargos - op.monto_pagado) AS saldo
            FROM obligaciones_fiscales of
            LEFT JOIN predios p ON p.id = of.predio_id
            JOIN obligacion_periodo op ON op.obligacion_id = of.id
            JOIN periodos_fiscales pf ON pf.id = op.periodo_id
            WHERE of.contribuyente_id = :cid AND of.activo = TRUE
            ORDER BY of.id, pf.anio DESC
        ');
        $stmt->execute([':cid' => $contribuyenteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registra un pago y lo aplica a obligaciones pendientes
     */
    public function registrarPago(int $contribuyenteId, float $monto, string $metodoPago, array $obligacionPeriodoIds, ?string $referencia = null): int {
        $this->db->beginTransaction();
        try {
            if (!$referencia) {
                $referencia = 'PAY-' . date('YmdHis') . '-' . rand(1000, 9999);
            }

            $stmtPago = $this->db->prepare('
                INSERT INTO pagos (contribuyente_id, monto, referencia, metodo_pago)
                VALUES (:cid, :monto, :ref, :metodo)
                RETURNING id
            ');
            $stmtPago->execute([
                ':cid'    => $contribuyenteId,
                ':monto'  => $monto,
                ':ref'    => $referencia,
                ':metodo' => $metodoPago,
            ]);
            $pagoId = (int)$stmtPago->fetchColumn();

            // Aplicar a cada obligación-periodo
            foreach ($obligacionPeriodoIds as $opId) {
                $opId = (int)$opId;
                // Verificar que la obligación pertenece al contribuyente
                $stmtOwner = $this->db->prepare('
                    SELECT op.id FROM obligacion_periodo op
                    JOIN obligaciones_fiscales of2 ON of2.id = op.obligacion_id
                    WHERE op.id = :opid AND of2.contribuyente_id = :cid AND op.pagado = FALSE
                ');
                $stmtOwner->execute([':opid' => $opId, ':cid' => $contribuyenteId]);
                if (!$stmtOwner->fetchColumn()) {
                    logIDOR('IDOR_PAGO_CONTRIBUYENTE', ['contribuyente_id' => $contribuyenteId, 'op_id_rechazado' => $opId]);
                    throw new RuntimeException('Obligación no válida o no pertenece al contribuyente.');
                }

                $stmtOp = $this->db->prepare('SELECT monto + monto_recargos - monto_pagado AS pendiente FROM obligacion_periodo WHERE id = :id');
                $stmtOp->execute([':id' => $opId]);
                $pendiente = (float)$stmtOp->fetchColumn();

                $stmtAplic = $this->db->prepare('
                    INSERT INTO pago_aplicacion (pago_id, obligacion_periodo_id, monto_aplicado)
                    VALUES (:pago_id, :op_id, :monto)
                ');
                $stmtAplic->execute([':pago_id' => $pagoId, ':op_id' => $opId, ':monto' => $pendiente]);

                // Marcar como pagado
                $stmtMarca = $this->db->prepare('UPDATE obligacion_periodo SET pagado = TRUE, monto_pagado = monto + monto_recargos WHERE id = :id');
                $stmtMarca->execute([':id' => $opId]);
            }

            $this->db->commit();
            logPago('PAGO_REGISTRADO', ['contribuyente_id' => $contribuyenteId, 'monto' => $monto, 'pago_id' => $pagoId]);
            return $pagoId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Obtiene historial de pagos de un contribuyente
     */
    public function getPagosContribuyente(int $contribuyenteId): array {
        $stmt = $this->db->prepare('
            SELECT pg.*, COUNT(pa.id) AS items_aplicados
            FROM pagos pg
            LEFT JOIN pago_aplicacion pa ON pa.pago_id = pg.id
            WHERE pg.contribuyente_id = :cid
            GROUP BY pg.id
            ORDER BY pg.fecha DESC
        ');
        $stmt->execute([':cid' => $contribuyenteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
