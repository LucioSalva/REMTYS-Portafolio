<?php
/**
 * RequisitosService — Clasificación documental por trámite
 *
 * Clasificaciones posibles:
 *   CUMPLE       — existe, aprobado, vigente, reutilizable de este expediente
 *   REUTILIZABLE — existe en otro expediente, puede reutilizarse
 *   PENDIENTE    — documento ya cargado, esperando validación del admin
 *                  (cubre estados de BD 'pendiente' y 'en_revision')
 *   VENCIDO      — existe pero fecha_vigencia < hoy
 *   INVALIDO     — fue rechazado en validación
 *   FALTANTE     — no existe ningún documento de ese tipo
 *   ESPECIFICO   — obligatorio y específico del trámite — SIN documento cargado
 *   EXCLUSIVO    — evidencia exclusiva de este expediente — SIN documento cargado
 *
 * Prioridad de selección de "mejor documento" (de mayor a menor):
 *   aprobado > pendiente/en_revision > vencido > rechazado > ninguno
 */

class RequisitosService {

    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Evalúa todos los requisitos documentales de un trámite para un contribuyente
     *
     * @param int $tramiteId
     * @param int $contribuyenteId
     * @param int|null $expedienteId  — para detectar documentos exclusivos del expediente
     * @return array[]  Lista de requisitos con clasificación y documento asociado
     */
    public function evaluarRequisitos(int $tramiteId, int $contribuyenteId, ?int $expedienteId = null): array {
        // 1. Obtener los requisitos del tipo de trámite
        $sqlRequisitos = '
            SELECT
                rd.id                       AS requisito_id,
                rd.tipo_tramite_id,
                rd.tipo_documento_id,
                td.nombre                   AS tipo_documento_nombre,
                td.clave                    AS tipo_documento_clave,
                td.reutilizable             AS tipo_reutilizable,
                td.requiere_vigencia,
                td.meses_vigencia,
                td.extensiones_permitidas,
                td.tamano_maximo_mb,
                rd.obligatorio,
                rd.especifico_tramite,
                rd.exclusivo_expediente,
                rd.descripcion_instruccion,
                rd.orden_visualizacion,
                t.expediente_id,
                t.tipo_tramite_id           AS tramite_tipo_tramite_id
            FROM tramites t
            JOIN requisitos_documentales rd ON rd.tipo_tramite_id = t.tipo_tramite_id
            JOIN tipos_documento td ON td.id = rd.tipo_documento_id
            WHERE t.id = :tramite_id
              AND td.activo = TRUE
            ORDER BY rd.orden_visualizacion ASC, rd.id ASC
        ';
        $stmt = $this->db->prepare($sqlRequisitos);
        $stmt->execute([':tramite_id' => $tramiteId]);
        $requisitos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($requisitos)) {
            return [];
        }

        $expId = $expedienteId ?? ($requisitos[0]['expediente_id'] ?? null);

        // 2. Obtener documentos del contribuyente (versiones activas)
        $sqlDocs = '
            SELECT
                d.id,
                d.tipo_documento_id,
                d.estado_validacion,
                d.fecha_vigencia,
                d.reutilizable,
                d.exclusivo_expediente_id,
                d.es_version_activa,
                d.nombre_original,
                d.nombre_archivo,
                d.ruta_archivo,
                d.numero_folio,
                d.fecha_emision,
                d.created_at,
                td.requiere_vigencia
            FROM documentos d
            JOIN tipos_documento td ON td.id = d.tipo_documento_id
            WHERE d.contribuyente_id = :contrib_id
              AND d.es_version_activa = TRUE
            ORDER BY d.created_at DESC
        ';
        $stmtDocs = $this->db->prepare($sqlDocs);
        $stmtDocs->execute([':contrib_id' => $contribuyenteId]);
        $documentos = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

        // Indexar documentos por tipo_documento_id
        $docsPorTipo = [];
        foreach ($documentos as $doc) {
            $tid = $doc['tipo_documento_id'];
            if (!isset($docsPorTipo[$tid])) {
                $docsPorTipo[$tid] = [];
            }
            $docsPorTipo[$tid][] = $doc;
        }

        // 3. Clasificar cada requisito
        $resultado = [];
        foreach ($requisitos as $req) {
            $tipoDocId = $req['tipo_documento_id'];
            $docs = $docsPorTipo[$tipoDocId] ?? [];

            $clasificacion = $this->clasificarRequisito($req, $docs, $expId);
            $resultado[] = array_merge($req, $clasificacion);
        }

        return $resultado;
    }

    /**
     * Clasifica un requisito dado los documentos disponibles del contribuyente.
     *
     * Estrategia: itera sobre los documentos y elige el de MAYOR prioridad según el
     * rank definido en PRIORIDAD_DOC. Un documento mejor nunca es degradado por uno
     * peor (p.ej. un 'aprobado' ya elegido no se reemplaza por un 'pendiente' más nuevo).
     * Si al finalizar no hay ningún documento utilizable, aplica las reglas "sin doc".
     */
    private function clasificarRequisito(array $req, array $docs, ?int $expedienteId): array {
        $hoy = new DateTime();

        // --- Caso A: no hay documentos cargados del tipo requerido ---
        if (empty($docs)) {
            return $this->resultadoSinDocumento($req);
        }

        // Rank de prioridad: mayor = mejor candidato a "representar" el requisito
        // CUMPLE=100, REUTILIZABLE=90, PENDIENTE=70, VENCIDO=40, INVALIDO=20, ninguno=0
        $prioridadClas = [
            'CUMPLE'       => 100,
            'REUTILIZABLE' => 90,
            'PENDIENTE'    => 70,
            'VENCIDO'      => 40,
            'INVALIDO'     => 20,
        ];

        $mejorDoc       = null;
        $mejorClas      = null;
        $mejorPrioridad = -1;

        foreach ($docs as $doc) {
            // Calcular clasificación candidata que aportaría ESTE documento
            $candidata = $this->clasificacionCandidata($doc, $req, $expedienteId, $hoy);
            if ($candidata === null) {
                // Documento no utilizable para este requisito (p.ej. exclusivo de otro expediente)
                continue;
            }

            $prio = $prioridadClas[$candidata] ?? 0;
            if ($prio > $mejorPrioridad) {
                $mejorPrioridad = $prio;
                $mejorClas      = $candidata;
                $mejorDoc       = $doc;
                // Si ya encontramos un CUMPLE, no hace falta seguir buscando
                if ($candidata === 'CUMPLE') {
                    break;
                }
            }
        }

        // Si ningún documento resultó utilizable, comportarse como si no hubiera
        if ($mejorClas === null) {
            return $this->resultadoSinDocumento($req);
        }

        return $this->resultado($mejorClas, $mejorDoc);
    }

    /**
     * Devuelve la clasificación que aportaría un documento concreto a un requisito,
     * o null si el documento NO puede usarse para cubrir el requisito.
     */
    private function clasificacionCandidata(array $doc, array $req, ?int $expedienteId, DateTime $hoy): ?string {
        $estado = $doc['estado_validacion'] ?? null;

        // Documento exclusivo de OTRO expediente → no utilizable aquí
        $esExclusivoOtroExp = ($doc['exclusivo_expediente_id'] !== null
            && $doc['exclusivo_expediente_id'] != $expedienteId);
        if ($esExclusivoOtroExp) {
            return null;
        }

        // Rechazado: aporta únicamente la señal INVALIDO (sin importar vigencia/tipo)
        if ($estado === 'rechazado') {
            return 'INVALIDO';
        }

        // Vencido (por fecha) — aplica a doc aprobado o pendiente que ya expiró
        $esVencido = false;
        if (!empty($doc['requiere_vigencia']) && !empty($doc['fecha_vigencia'])) {
            try {
                $fechaVig = new DateTime($doc['fecha_vigencia']);
                if ($fechaVig < $hoy) {
                    $esVencido = true;
                }
            } catch (Exception $e) {
                $esVencido = false;
            }
        }
        if ($esVencido) {
            return 'VENCIDO';
        }

        // Documento cargado y esperando validación admin
        // (cubre 'pendiente' y 'en_revision' del ENUM estado_validacion)
        if ($estado === 'pendiente' || $estado === 'en_revision') {
            return 'PENDIENTE';
        }

        // Documento aprobado y vigente → determinar si es CUMPLE o REUTILIZABLE
        if ($estado === 'aprobado') {
            // Si el documento pertenece a otro expediente pero es reutilizable
            if ($doc['exclusivo_expediente_id'] !== null
                && $doc['exclusivo_expediente_id'] != $expedienteId
            ) {
                // ya filtrado arriba, por seguridad
                return null;
            }

            // Documento libre (sin expediente) o del mismo expediente
            if (!empty($doc['reutilizable'])
                && $doc['exclusivo_expediente_id'] === null
                && empty($req['exclusivo_expediente'])
            ) {
                // Requisito no exige exclusividad y el doc es reutilizable libre
                return 'CUMPLE';
            }

            // Documento del mismo expediente (incluyendo exclusivos de este expediente)
            if ($doc['exclusivo_expediente_id'] == $expedienteId
                || $doc['exclusivo_expediente_id'] === null
            ) {
                return 'CUMPLE';
            }

            // Doc aprobado reutilizable que proviene de otro expediente:
            // marca como REUTILIZABLE (puede reciclarse con confirmación del usuario)
            if (!empty($doc['reutilizable'])) {
                return 'REUTILIZABLE';
            }

            return null;
        }

        // Estado desconocido → ignorar
        return null;
    }

    /**
     * Clasificación cuando NO existe ningún documento utilizable del tipo requerido.
     */
    private function resultadoSinDocumento(array $req): array {
        if (!empty($req['especifico_tramite'])) {
            return $this->resultado('ESPECIFICO', null);
        }
        if (!empty($req['exclusivo_expediente'])) {
            return $this->resultado('EXCLUSIVO', null);
        }
        return $this->resultado('FALTANTE', null);
    }

    private function resultado(string $clasificacion, ?array $documento): array {
        return [
            'clasificacion'    => $clasificacion,
            'documento'        => $documento,
            // puede_reutilizar: cuenta como cubierto (doc válido y aprobado)
            'puede_reutilizar' => in_array($clasificacion, ['CUMPLE', 'REUTILIZABLE'], true),
            // requiere_subir: el usuario debe subir un documento nuevo.
            // PENDIENTE NO requiere subir — el documento ya está, falta validación admin.
            'requiere_subir'   => in_array($clasificacion, ['FALTANTE', 'VENCIDO', 'INVALIDO', 'ESPECIFICO', 'EXCLUSIVO'], true),
        ];
    }

    /**
     * Resumen de completitud de requisitos.
     *
     * Distingue dos métricas clave:
     *   - aprobados: requisitos con documento APROBADO (CUMPLE o REUTILIZABLE).
     *                Es lo único que permite "listo_para_envio".
     *   - cargados : aprobados + en_revision. Es lo que el usuario "ve" en la tabla
     *                con archivo asociado y debe reflejarse en la barra principal
     *                para que se mueva visiblemente al subir un documento.
     *
     * Mantiene las claves legacy ('cumplidos', 'porcentaje') para no romper vistas
     * existentes, pero con nueva semántica: 'porcentaje' = porcentaje_cargado.
     */
    public function calcularCompletitud(array $requisitosEvaluados): array {
        $total        = count($requisitosEvaluados);
        $obligatorios = array_values(array_filter(
            $requisitosEvaluados,
            fn($r) => !empty($r['obligatorio'])
        ));
        $totalObligatorios = count($obligatorios);

        $aprobados  = 0;   // CUMPLE + REUTILIZABLE
        $enRevision = 0;   // PENDIENTE
        $vencidos   = 0;   // VENCIDO
        $invalidos  = 0;   // INVALIDO
        $faltantes  = 0;   // FALTANTE + ESPECIFICO + EXCLUSIVO (sin doc)

        foreach ($obligatorios as $r) {
            switch ($r['clasificacion']) {
                case 'CUMPLE':
                case 'REUTILIZABLE':
                    $aprobados++;
                    break;
                case 'PENDIENTE':
                    $enRevision++;
                    break;
                case 'VENCIDO':
                    $vencidos++;
                    break;
                case 'INVALIDO':
                    $invalidos++;
                    break;
                case 'FALTANTE':
                case 'ESPECIFICO':
                case 'EXCLUSIVO':
                default:
                    $faltantes++;
                    break;
            }
        }

        $cargados = $aprobados + $enRevision;

        $porcentajeAprobado = $totalObligatorios > 0
            ? (int) round(($aprobados / $totalObligatorios) * 100)
            : 0;
        $porcentajeCargado = $totalObligatorios > 0
            ? (int) round(($cargados / $totalObligatorios) * 100)
            : 0;

        // "Listo" exige aprobación efectiva Y que existan obligatorios.
        // Un trámite sin requisitos obligatorios NO se considera listo automáticamente.
        $listoParaEnvio = ($totalObligatorios > 0) && ($aprobados >= $totalObligatorios);

        return [
            // --- Totales base ---
            'total'              => $total,
            'total_obligatorios' => $totalObligatorios,

            // --- Métricas nuevas (granulares) ---
            'aprobados'          => $aprobados,
            'en_revision'        => $enRevision,
            'cargados'           => $cargados,
            'vencidos'           => $vencidos,
            'invalidos'          => $invalidos,
            'faltantes'          => $faltantes,

            // --- Porcentajes ---
            'porcentaje_aprobado'=> $porcentajeAprobado,
            'porcentaje_cargado' => $porcentajeCargado,

            // --- Estado global ---
            'listo_para_envio'   => $listoParaEnvio,

            // --- Compatibilidad con vistas legacy (mantener) ---
            // 'cumplidos' = aprobados estrictos (lo que históricamente se contaba)
            'cumplidos'          => $aprobados,
            // 'porcentaje' ahora refleja CARGADO (incluye en revisión) para que la
            // barra se mueva cuando el usuario sube un documento. El gate de envío
            // sigue siendo 'listo_para_envio' = sólo aprobados.
            'porcentaje'         => $porcentajeCargado,
        ];
    }

    /**
     * Verifica ciclos en dependencias de trámites (DFS iterativo)
     */
    public function tieneCiclo(int $tramiteId, int $nuevoDepId): bool {
        if ($tramiteId === $nuevoDepId) return true;

        $visitados = [];
        $pila = [$nuevoDepId];

        while (!empty($pila)) {
            $actual = array_pop($pila);
            if (isset($visitados[$actual])) continue;
            $visitados[$actual] = true;

            if ($actual === $tramiteId) return true;

            $stmt = $this->db->prepare('SELECT depende_de_tramite_id FROM tramite_dependencias WHERE tramite_id = :tid');
            $stmt->execute([':tid' => $actual]);
            $deps = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($deps as $dep) {
                if (!isset($visitados[$dep])) {
                    $pila[] = (int)$dep;
                }
            }
        }

        return false;
    }
}
