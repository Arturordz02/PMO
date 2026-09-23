<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Cache;

/**
 * PMO SOLUTIONS — Modelo de Habilidades Blandas (SoftSkillsModel)
 *
 * Gestiona el catálogo de competencias, los escenarios situacionales,
 * la lógica de puntuación y la generación de reportes detallados.
 *
 * Competencias evaluadas:
 *  1. Comunicación
 *  2. Trabajo en Equipo
 *  3. Resolución de Problemas
 *  4. Adaptabilidad
 *
 * Sistema de puntuación:
 *  - Cada competencia: 5 preguntas situacionales × 25 puntos máx. = 100 pts.
 *  - Score global: promedio ponderado de las 4 competencias
 *  - Niveles: Inicial (0-39) | En Desarrollo (40-59) | Competente (60-79) | Sobresaliente (80-100)
 */
class SoftSkillsModel extends Model {

    /** Puntaje máximo por pregunta */
    const MAX_SCORE_PER_QUESTION = 25;

    /** Número de preguntas por competencia */
    const QUESTIONS_PER_COMPETENCY = 5;

    /** Niveles de madurez con umbrales */
    const MATURITY_LEVELS = [
        ['min' => 80, 'max' => 100, 'level' => 'Sobresaliente', 'color' => '#27ae60', 'icon' => '🏆'],
        ['min' => 60, 'max' => 79,  'level' => 'Competente',    'color' => '#2980b9', 'icon' => '⭐'],
        ['min' => 40, 'max' => 59,  'level' => 'En Desarrollo', 'color' => '#f39c12', 'icon' => '📈'],
        ['min' => 0,  'max' => 39,  'level' => 'Inicial',       'color' => '#e74c3c', 'icon' => '🌱'],
    ];

    /**
     * Retorna el catálogo completo de competencias con sus escenarios situacionales.
     * Cacheado por 24h (el catálogo es inmutable en producción).
     *
     * @return array Catálogo indexado por slug de competencia
     */
    public function getCatalog(): array {
        return Cache::remember('softskills_catalog_v1', 86400, function () {
            return $this->buildCatalog();
        });
    }

    /**
     * Calcula el score para una competencia dada un array de respuestas.
     *
     * @param  string $competency  Slug de la competencia
     * @param  array  $answers     Array de [pregunta_id => opcion_elegida] (opciones 1-4)
     * @return array               ['score' => int, 'details' => [...]]
     */
    public function calculateScore(string $competency, array $answers): array {
        $catalog   = $this->getCatalog();
        $questions = $catalog[$competency]['questions'] ?? [];
        $total     = 0;
        $details   = [];

        foreach ($questions as $index => $question) {
            $questionId   = $index + 1;
            $chosenOption = (int) ($answers[$questionId] ?? 0);

            // Validar que la opción esté en rango (1-4)
            if ($chosenOption < 1 || $chosenOption > 4) {
                $chosenOption = 1;
            }

            $optionData = $question['options'][$chosenOption - 1];
            $points     = $optionData['points'];
            $total     += $points;

            $details[] = [
                'question_id'   => $questionId,
                'question'      => $question['scenario'],
                'chosen_option' => $chosenOption,
                'chosen_text'   => $optionData['text'],
                'points'        => $points,
                'max_points'    => self::MAX_SCORE_PER_QUESTION,
                'feedback'      => $optionData['feedback'],
            ];
        }

        return [
            'competency'  => $competency,
            'label'       => $catalog[$competency]['label'] ?? $competency,
            'score'       => min(100, $total),
            'max_score'   => self::MAX_SCORE_PER_QUESTION * self::QUESTIONS_PER_COMPETENCY,
            'percentage'  => round(($total / (self::MAX_SCORE_PER_QUESTION * self::QUESTIONS_PER_COMPETENCY)) * 100, 1),
            'details'     => $details,
        ];
    }

    /**
     * Valida estrictamente las respuestas contra el catálogo oficial del backend.
     * Rechaza tipos no enteros, opciones fuera de rango (1-4), preguntas faltantes/adicionales
     * y competencias no reconocidas o ausentes.
     *
     * @param  mixed $answers
     * @return array ['valid' => bool, 'errors' => array, 'cleanAnswers' => array]
     */
    public function validateAnswersStrict(mixed $answers): array {
        $catalog = $this->getCatalog();
        $expectedCompetencies = array_keys($catalog);
        $errors = [];
        $cleanAnswers = [];

        if (!is_array($answers) || empty($answers)) {
            return [
                'valid' => false,
                'errors' => ['answers' => 'El formato de respuestas es inválido o está vacío.'],
                'cleanAnswers' => []
            ];
        }

        // 1. Validar que no existan competencias no reconocidas
        $submittedCompetencies = array_keys($answers);
        $extraCompetencies = array_diff($submittedCompetencies, $expectedCompetencies);
        if (!empty($extraCompetencies)) {
            $errors['competencies_extra'] = 'Se enviaron competencias no reconocidas en el catálogo oficial: ' . implode(', ', $extraCompetencies);
        }

        // 2. Validar que no falten competencias
        $missingCompetencies = array_diff($expectedCompetencies, $submittedCompetencies);
        if (!empty($missingCompetencies)) {
            $errors['competencies_missing'] = 'Faltan respuestas para las siguientes competencias obligatorias: ' . implode(', ', $missingCompetencies);
        }

        // 3. Validar preguntas y opciones por cada competencia del catálogo
        foreach ($expectedCompetencies as $compKey) {
            if (!isset($answers[$compKey]) || !is_array($answers[$compKey])) {
                if (!isset($errors['competencies_missing'])) {
                    $errors[$compKey] = "Las respuestas de la competencia '{$compKey}' deben ser un objeto válido.";
                }
                continue;
            }

            $compAnswers = $answers[$compKey];
            $questions = $catalog[$compKey]['questions'] ?? [];
            $totalExpectedQuestions = count($questions);

            // Validar cantidad exacta de preguntas
            $validQIds = range(1, $totalExpectedQuestions);
            $validQIdStrings = array_map('strval', $validQIds);
            $submittedQKeys = array_map('strval', array_keys($compAnswers));

            $extraQKeys = array_diff($submittedQKeys, $validQIdStrings);
            if (!empty($extraQKeys)) {
                $errors["{$compKey}_extra_questions"] = "Se enviaron preguntas no existentes o duplicadas en '{$compKey}'.";
            }

            $missingQKeys = array_diff($validQIdStrings, $submittedQKeys);
            if (!empty($missingQKeys)) {
                $errors["{$compKey}_missing_questions"] = "Faltan preguntas por responder en '{$compKey}': " . implode(', ', $missingQKeys);
            }

            $cleanAnswers[$compKey] = [];

            // Validar cada pregunta individualmente
            foreach ($validQIds as $qId) {
                $rawVal = $compAnswers[$qId] ?? ($compAnswers[(string)$qId] ?? null);

                // REGLA ESTRICTA: Las opciones deben ser enteros reales del 1 al 4. Rechazar strings numéricos, booleanos, floats, arrays.
                if (!is_int($rawVal)) {
                    $errors["{$compKey}_{$qId}_type"] = "La respuesta a la pregunta {$qId} de '{$compKey}' debe ser un número entero (opciones 1 a 4).";
                    continue;
                }

                if ($rawVal < 1 || $rawVal > 4) {
                    $errors["{$compKey}_{$qId}_range"] = "La opción seleccionada ({$rawVal}) en la pregunta {$qId} de '{$compKey}' no existe en el catálogo.";
                    continue;
                }

                $cleanAnswers[$compKey][$qId] = $rawVal;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'cleanAnswers' => $cleanAnswers
        ];
    }

    /**
     * Calcula el score global y determina el nivel de madurez dinámicamente desde el catálogo.
     *
     * @param  array $competencyScores  Array de ['competency' => score_int, ...]
     * @return array                    Resultado completo con nivel y recomendaciones
     */
    public function calculateGlobalScore(array $competencyScores): array {
        $catalog = $this->getCatalog();
        $scores = [];
        foreach (array_keys($catalog) as $comp) {
            $scores[$comp] = (int) ($competencyScores[$comp] ?? 0);
        }

        $count = count($scores) ?: 1;
        $globalScore = (int) round(array_sum($scores) / $count);
        $maturity    = $this->getMaturityLevel($globalScore);

        return [
            'global_score'  => $globalScore,
            'scores'        => $scores,
            'maturity_level'=> $maturity['level'],
            'maturity_color'=> $maturity['color'],
            'maturity_icon' => $maturity['icon'],
            'strengths'     => $this->identifyStrengths($scores),
            'areas_to_improve' => $this->identifyAreasToImprove($scores),
            'recommendations'  => $this->getRecommendations($maturity['level'], $scores),
            'radar_data'       => $this->buildRadarData($scores),
        ];
    }

    /**
     * Genera un reporte completo en formato array (serializable a JSON).
     *
     * @param  string $codigoEvaluacion Código único de la evaluación
     * @param  array  $participantData  Datos del participante
     * @param  array  $allAnswers       Respuestas por competencia
     * @return array                    Reporte estructurado completo
     */
    public function generateReport(
        string $codigoEvaluacion,
        array $participantData,
        array $allAnswers
    ): array {
        $catalog          = $this->getCatalog();
        $competencyResults = [];

        foreach (array_keys($catalog) as $competency) {
            $answers              = $allAnswers[$competency] ?? [];
            $competencyResults[$competency] = $this->calculateScore($competency, $answers);
        }

        $scoresOnly  = array_map(fn($r) => $r['score'], $competencyResults);
        $globalResult = $this->calculateGlobalScore($scoresOnly);

        return [
            'meta' => [
                'codigo_evaluacion' => $codigoEvaluacion,
                'version'           => '2.0',
                'generated_at'      => date('Y-m-d H:i:s'),
                'timezone'          => 'America/Lima',
            ],
            'participant' => [
                'nombre'   => $participantData['nombre_completo'] ?? '',
                'email'    => $participantData['email'] ?? '',
                'cargo'    => $participantData['cargo'] ?? '',
                'empresa'  => $participantData['empresa'] ?? '',
            ],
            'summary' => $globalResult,
            'competencies' => $competencyResults,
            'interpretation' => $this->getInterpretation($globalResult['maturity_level']),
        ];
    }

    /**
     * Persiste la evaluación en la base de datos con consultas optimizadas.
     *
     * @param  array       $report          Reporte generado por generateReport()
     * @param  array       $participantData Datos del participante
     * @param  string      $ip              Dirección IP del evaluado
     * @param  string      $userAgent       User-Agent del navegador
     * @param  string|null $tokenHash       Hash SHA-256 del token de acceso al reporte
     * @return int|false                    ID de la evaluación insertada o false
     */
    public function saveEvaluation(
        array $report,
        array $participantData,
        string $ip = '0.0.0.0',
        string $userAgent = '',
        ?string $tokenHash = null
    ): int|false {
        $pdo = \App\Core\Database::getConnection();
        if (!$pdo) {
            return false; // Graceful degradation: DB no disponible
        }

        $summary = $report['summary'];

        // Inserción principal en una sola query optimizada
        $sql = "INSERT INTO `evaluaciones_habilidades_blandas`
                    (codigo_evaluacion, nombre_completo, email, cargo, empresa,
                     tipo_evaluacion, score_comunicacion, score_trabajo_equipo,
                     score_resolucion, score_adaptabilidad, score_global,
                     nivel_madurez, reporte_json, ip_address, user_agent, estado, report_access_token_hash)
                VALUES
                    (:codigo, :nombre, :email, :cargo, :empresa,
                     :tipo, :score_com, :score_te, :score_res, :score_ada,
                     :score_global, :nivel, :reporte, :ip, :ua, 'Completada', :token_hash)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':codigo'       => $report['meta']['codigo_evaluacion'],
            ':nombre'       => $participantData['nombre_completo'] ?? '',
            ':email'        => $participantData['email'] ?? '',
            ':cargo'        => $participantData['cargo'] ?? null,
            ':empresa'      => $participantData['empresa'] ?? null,
            ':tipo'         => $participantData['tipo_evaluacion'] ?? 'Individual',
            ':score_com'    => $summary['scores']['comunicacion'],
            ':score_te'     => $summary['scores']['trabajo_equipo'],
            ':score_res'    => $summary['scores']['resolucion_problemas'],
            ':score_ada'    => $summary['scores']['adaptabilidad'],
            ':score_global' => $summary['global_score'],
            ':nivel'        => $summary['maturity_level'],
            ':reporte'      => base64_encode(gzcompress(json_encode($report), 6)),
            ':ip'           => $ip,
            ':ua'           => substr($userAgent, 0, 255),
            ':token_hash'   => $tokenHash,
        ]);

        $evaluationId = (int) $pdo->lastInsertId();

        // Insertar respuestas individuales en batch (una sola query)
        $this->saveAnswersBatch($pdo, $evaluationId, $report['competencies']);

        // Invalidar cachés relacionadas
        Cache::delete('softskills_ranking');
        Cache::delete('softskills_averages');

        return $evaluationId;
    }

    /**
     * Consulta directa (sin caché) del hash del token para verificación de autorización
     *
     * @param string $codigo Código de evaluación (EVA-2026-XXXXX)
     * @return array|null ['id' => int, 'codigo_evaluacion' => string, 'report_access_token_hash' => ?string]
     */
    public function getAuthHashByCode(string $codigo): ?array {
        $pdo = \App\Core\Database::getConnection();
        if (!$pdo) {
            return null;
        }

        $stmt = $pdo->prepare(
            "SELECT id, codigo_evaluacion, report_access_token_hash
             FROM `evaluaciones_habilidades_blandas`
             WHERE `codigo_evaluacion` = :codigo AND `estado` = 'Completada'
             LIMIT 1"
        );
        $stmt->execute([':codigo' => $codigo]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Obtiene el reporte minimizado para el participante (sin credenciales, sin hash, sin IP, sin email)
     *
     * @param  string $codigo Código de evaluación (EVA-2026-XXXXX)
     * @return array|null
     */
    public function getPublicReport(string $codigo): ?array {
        $cacheKey = 'pub_eval_' . md5($codigo);
        return Cache::remember($cacheKey, 1800, function () use ($codigo) {
            $pdo = \App\Core\Database::getConnection();
            if (!$pdo) {
                return null;
            }

            $stmt = $pdo->prepare(
                "SELECT codigo_evaluacion, nombre_completo, cargo, empresa,
                        tipo_evaluacion, score_comunicacion, score_trabajo_equipo,
                        score_resolucion, score_adaptabilidad, score_global,
                        nivel_madurez, reporte_json, fecha_registro
                 FROM `evaluaciones_habilidades_blandas`
                 WHERE `codigo_evaluacion` = :codigo AND `estado` = 'Completada'
                 LIMIT 1"
            );
            $stmt->execute([':codigo' => $codigo]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row) {
                return null;
            }

            $reporteDecoded = null;
            if (!empty($row['reporte_json'])) {
                $decompressed = @gzuncompress(base64_decode($row['reporte_json']));
                if ($decompressed !== false) {
                    $reporteDecoded = json_decode($decompressed, true);
                }
            }

            // Sanitizar participante eliminando email de la estructura interna
            if (is_array($reporteDecoded) && isset($reporteDecoded['participant'])) {
                unset($reporteDecoded['participant']['email']);
            }

            return [
                'codigo_evaluacion' => $row['codigo_evaluacion'],
                'participant' => [
                    'nombre'  => $row['nombre_completo'],
                    'cargo'   => $row['cargo'],
                    'empresa' => $row['empresa'],
                ],
                'tipo_evaluacion' => $row['tipo_evaluacion'],
                'scores' => [
                    'comunicacion'         => (int) $row['score_comunicacion'],
                    'trabajo_equipo'       => (int) $row['score_trabajo_equipo'],
                    'resolucion_problemas' => (int) $row['score_resolucion'],
                    'adaptabilidad'        => (int) $row['score_adaptabilidad'],
                    'global'               => (int) $row['score_global'],
                ],
                'nivel_madurez'   => $row['nivel_madurez'],
                'fecha_registro'  => $row['fecha_registro'],
                'reporte_detalle' => $reporteDecoded,
            ];
        });
    }

    /**
     * Obtiene una evaluación completa para uso interno administrativo
     *
     * @param  string $codigo Código de evaluación (EVA-2026-XXXXX)
     * @return array|null
     */
    public function getByCode(string $codigo): ?array {
        $cacheKey = 'eval_' . md5($codigo);
        return Cache::remember($cacheKey, 1800, function () use ($codigo) {
            $pdo = \App\Core\Database::getConnection();
            if (!$pdo) {
                return null;
            }

            $stmt = $pdo->prepare(
                "SELECT id, codigo_evaluacion, nombre_completo, email, cargo, empresa,
                        tipo_evaluacion, score_comunicacion, score_trabajo_equipo,
                        score_resolucion, score_adaptabilidad, score_global,
                        nivel_madurez, reporte_json, ip_address, user_agent, estado, fecha_registro
                 FROM `evaluaciones_habilidades_blandas`
                 WHERE `codigo_evaluacion` = :codigo AND `estado` = 'Completada'
                 LIMIT 1"
            );
            $stmt->execute([':codigo' => $codigo]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($row && !empty($row['reporte_json'])) {
                $decompressed = @gzuncompress(base64_decode($row['reporte_json']));
                if ($decompressed !== false) {
                    $row['reporte_json'] = json_decode($decompressed, true);
                }
            }

            return $row ?: null;
        });
    }

    /**
     * Lista evaluaciones con paginación obligatoria.
     * Optimizado con índice idx_eval_estado_fecha.
     *
     * @param  int    $page    Página actual (1-based)
     * @param  int    $perPage Resultados por página (máx 50)
     * @return array           ['data' => [...], 'meta' => [...]]
     */
    public function listPaginated(int $page = 1, int $perPage = 20): array {
        $perPage = min(50, max(1, $perPage));
        $offset  = ($page - 1) * $perPage;

        $pdo = \App\Core\Database::getConnection();
        if (!$pdo) {
            return ['data' => [], 'meta' => ['total' => 0, 'current_page' => $page, 'per_page' => $perPage, 'last_page' => 1]];
        }

        // Total count con índice (sin SELECT *)
        $countStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM `evaluaciones_habilidades_blandas` WHERE `estado` = 'Completada'"
        );
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        // Datos paginados (columnas selectivas, sin reporte_json pesado)
        $dataStmt = $pdo->prepare(
            "SELECT id, codigo_evaluacion, nombre_completo, email, cargo, empresa,
                    score_global, nivel_madurez, tipo_evaluacion, fecha_registro
             FROM `evaluaciones_habilidades_blandas`
             WHERE `estado` = 'Completada'
             ORDER BY `fecha_registro` DESC
             LIMIT :limit OFFSET :offset"
        );
        $dataStmt->bindValue(':limit',  $perPage, \PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset,  \PDO::PARAM_INT);
        $dataStmt->execute();

        return [
            'data' => $dataStmt->fetchAll(),
            'meta' => [
                'total'        => $total,
                'current_page' => $page,
                'per_page'     => $perPage,
                'last_page'    => (int) ceil($total / $perPage),
                'from'         => $offset + 1,
                'to'           => min($offset + $perPage, $total),
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE METHODS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Inserta todas las respuestas en un batch (una sola transacción).
     */
    private function saveAnswersBatch(\PDO $pdo, int $evaluationId, array $competencyResults): void {
        $values     = [];
        $placeholders = [];
        $i = 0;

        foreach ($competencyResults as $competency => $result) {
            foreach ($result['details'] as $detail) {
                $placeholders[] = "(:eval_id_{$i}, :comp_{$i}, :qid_{$i}, :opt_{$i}, :pts_{$i})";
                $values[":eval_id_{$i}"] = $evaluationId;
                $values[":comp_{$i}"]    = $competency;
                $values[":qid_{$i}"]     = $detail['question_id'];
                $values[":opt_{$i}"]     = $detail['chosen_option'];
                $values[":pts_{$i}"]     = $detail['points'];
                $i++;
            }
        }

        if (empty($placeholders)) {
            return;
        }

        $sql = "INSERT INTO `respuestas_evaluacion`
                    (evaluacion_id, competencia, pregunta_id, opcion_elegida, puntaje_obtenido)
                VALUES " . implode(', ', $placeholders);

        $pdo->prepare($sql)->execute($values);
    }

    /**
     * Determina el nivel de madurez según el score global.
     */
    private function getMaturityLevel(int $score): array {
        foreach (self::MATURITY_LEVELS as $level) {
            if ($score >= $level['min'] && $score <= $level['max']) {
                return $level;
            }
        }
        return self::MATURITY_LEVELS[3]; // Inicial por defecto
    }

    /**
     * Identifica las 2 fortalezas más altas.
     */
    private function identifyStrengths(array $scores): array {
        $labels = $this->getCompetencyLabels();
        arsort($scores);
        $top2 = array_slice($scores, 0, 2, true);

        return array_map(
            fn($k, $v) => ['competency' => $k, 'label' => $labels[$k] ?? $k, 'score' => $v],
            array_keys($top2),
            $top2
        );
    }

    /**
     * Identifica las 2 áreas con mayor oportunidad de mejora.
     */
    private function identifyAreasToImprove(array $scores): array {
        $labels = $this->getCompetencyLabels();
        asort($scores);
        $bottom2 = array_slice($scores, 0, 2, true);

        return array_map(
            fn($k, $v) => ['competency' => $k, 'label' => $labels[$k] ?? $k, 'score' => $v],
            array_keys($bottom2),
            $bottom2
        );
    }

    /**
     * Mapeo de competencias blandas a programas de especialización técnica de PMO Solutions.
     */
    private const COMPETENCY_COURSE_MAP = [
        'comunicacion' => [
            'course' => 'Contratos NEC4: ECC & Sustentación de Eventos Compensables',
            'action' => 'Para fortalecer tu comunicación contractual formal, gestión de alertas tempranas y sustentación técnica de reclamos en obra, te recomendamos especializarte en Contratos NEC4: ECC o Sustentación de Eventos Compensables.',
        ],
        'trabajo_equipo' => [
            'course' => 'Virtual Design & Construction (VDC / BIM) & Primavera P6',
            'action' => 'Para potenciar la coordinación multidisciplinaria e ingeniería concurrente (sesiones ICE) con equipos de obra y oficina técnica, te recomendamos cursar Virtual Design and Construction (VDC) & BIM.',
        ],
        'resolucion_problemas' => [
            'course' => 'Juntas de Resolución de Disputas (DAB / JRD) & Análisis Forense',
            'action' => 'Para desarrollar criterios ejecutivos en la prevención y solución temprana de controversias y peritajes de obra, te recomendamos la especialización en Juntas de Resolución de Disputas (DAB - JRD) o Análisis Forense de Plazos.',
        ],
        'adaptabilidad' => [
            'course' => 'Gestión Integral de Riesgos PMI® & Análisis Cuantitativo Monte Carlo',
            'action' => 'Para dominar la toma ágil de decisiones bajo incertidumbre, contingencias complejas y cambios de alcance, te sugerimos cursar Gestión Integral de Riesgos bajo Estándares PMI® o Análisis Cuantitativo de Riesgos.',
        ],
    ];

    /**
     * Retorna recomendaciones personalizadas basadas en el nivel de madurez y los cursos de PMO Solutions.
     */
    private function getRecommendations(string $level, array $scores): array {
        // Ordenar competencias de menor a mayor puntaje para priorizar las brechas críticas
        asort($scores);
        $competencyKeys = array_keys($scores);
        $lowest1 = $competencyKeys[0] ?? 'comunicacion';
        $lowest2 = $competencyKeys[1] ?? 'resolucion_problemas';

        $recs = [];

        if ($level === 'Sobresaliente') {
            $recs[] = 'Tu perfil evidencia un sólido liderazgo técnico y solvencia en la toma de decisiones directivas en proyectos de infraestructura.';
            $recs[] = 'Para consolidar tu perfil directivo y gobernanza contractual de alta gerencia, te recomendamos los programas ejecutivos de Compliance Técnico y Legal en la Construcción y Gestión del Cambio en Contratos del Estado.';
            $recs[] = 'Te sugerimos especializarte como miembro de Dispute Boards o perito de parte cursando Juntas de Resolución de Disputas (DAB - JRD) y Análisis Forense de Atrasos.';
        } elseif ($level === 'Competente') {
            $recs[] = 'Demuestras un nivel competente y equilibrado; cerrar brechas puntuales acelerará tu proyección hacia roles de Gerencia de Proyecto o Dirección Técnica.';
            if (isset(self::COMPETENCY_COURSE_MAP[$lowest1])) {
                $recs[] = self::COMPETENCY_COURSE_MAP[$lowest1]['action'];
            }
            if (isset(self::COMPETENCY_COURSE_MAP[$lowest2])) {
                $recs[] = self::COMPETENCY_COURSE_MAP[$lowest2]['action'];
            }
        } elseif ($level === 'En Desarrollo') {
            $recs[] = 'Identificamos un potencial valioso de crecimiento para asumir mayores responsabilidades en proyectos de alta complejidad técnica y contractual.';
            if (isset(self::COMPETENCY_COURSE_MAP[$lowest1])) {
                $recs[] = self::COMPETENCY_COURSE_MAP[$lowest1]['action'];
            }
            if (isset(self::COMPETENCY_COURSE_MAP[$lowest2])) {
                $recs[] = self::COMPETENCY_COURSE_MAP[$lowest2]['action'];
            }
            $recs[] = 'Aprovecha la modalidad 100% virtual online con clases grabadas en HD y acceso permanente 24/7 a los contenidos de PMO Solutions para capacitarte a tu propio ritmo.';
        } else { // Inicial
            $recs[] = 'Recomendamos priorizar el fortalecimiento de tus competencias clave para destacar en equipos de obra, supervisión y oficina técnica.';
            if (isset(self::COMPETENCY_COURSE_MAP[$lowest1])) {
                $recs[] = self::COMPETENCY_COURSE_MAP[$lowest1]['action'];
            }
            if (isset(self::COMPETENCY_COURSE_MAP[$lowest2])) {
                $recs[] = self::COMPETENCY_COURSE_MAP[$lowest2]['action'];
            }
            $recs[] = 'Comienza con nuestras especializaciones modulares grabadas 24/7 y descarga las plantillas editables y casos de aplicación real para acelerar tu aprendizaje.';
        }

        return $recs;
    }

    /**
     * Construye los datos para el gráfico radar (Chart.js).
     */
    private function buildRadarData(array $scores): array {
        $labels = $this->getCompetencyLabels();
        return [
            'labels'   => array_values($labels),
            'datasets' => [[
                'label'           => 'Tu Perfil de Habilidades',
                'data'            => [
                    $scores['comunicacion']         ?? 0,
                    $scores['trabajo_equipo']        ?? 0,
                    $scores['resolucion_problemas']  ?? 0,
                    $scores['adaptabilidad']         ?? 0,
                ],
                'backgroundColor' => 'rgba(26, 91, 130, 0.2)',
                'borderColor'     => 'rgba(26, 91, 130, 1)',
                'pointBackgroundColor' => 'rgba(26, 91, 130, 1)',
            ]],
        ];
    }

    /**
     * Retorna texto de interpretación según nivel.
     */
    private function getInterpretation(string $level): string {
        $interpretations = [
            'Sobresaliente' => 'Tu perfil de habilidades blandas es excepcional. Demuestras dominio en comunicación asertiva, colaboración estratégica, pensamiento sistémico y alta adaptabilidad al cambio. Estás preparado para liderar equipos de alta complejidad en proyectos de ingeniería y construcción.',
            'Competente'    => 'Tu perfil muestra solidez en las competencias evaluadas. Manejas adecuadamente las situaciones interpersonales y demuestras buena capacidad de trabajo en equipo. Con un desarrollo focalizado en tus áreas de oportunidad, puedes alcanzar el nivel Sobresaliente.',
            'En Desarrollo' => 'Tu perfil revela un desarrollo moderado de habilidades blandas. Tienes bases sólidas en algunas competencias y oportunidades claras de crecimiento en otras. Un plan de desarrollo estructurado te permitirá avanzar significativamente.',
            'Inicial'       => 'Tu evaluación indica que estás en las etapas iniciales del desarrollo de habilidades blandas. Esto es un punto de partida valioso: con el acompañamiento adecuado y práctica consistente, podrás fortalecer estas competencias esenciales para tu crecimiento profesional.',
        ];

        return $interpretations[$level] ?? '';
    }

    /**
     * Labels legibles de cada competencia.
     */
    private function getCompetencyLabels(): array {
        return [
            'comunicacion'         => 'Comunicación',
            'trabajo_equipo'       => 'Trabajo en Equipo',
            'resolucion_problemas' => 'Resolución de Problemas',
            'adaptabilidad'        => 'Adaptabilidad',
        ];
    }

    /**
     * Construye el catálogo completo de competencias con preguntas situacionales.
     * 4 competencias × 5 preguntas × 4 opciones ponderadas (25/18/10/0 pts).
     */
    private function buildCatalog(): array {
        return [

            // ─────────────────────────────────────────────────────────────────
            // COMPETENCIA 1: COMUNICACIÓN
            // ─────────────────────────────────────────────────────────────────
            'comunicacion' => [
                'label'       => 'Comunicación',
                'description' => 'Capacidad para transmitir ideas con claridad, escuchar activamente y adaptar el mensaje según la audiencia.',
                'icon'        => 'fas fa-comments',
                'color'       => '#1a5b82',
                'questions'   => [
                    [
                        'scenario' => 'Debes presentar el avance de un proyecto complejo al cliente y al equipo técnico simultáneamente. Ambos tienen perfiles muy distintos. ¿Cómo procedes?',
                        'options'  => [
                            ['text' => 'Preparo dos versiones: un resumen ejecutivo para el cliente y un informe técnico detallado para el equipo, usando lenguaje apropiado para cada audiencia.',
                             'points' => 25, 'feedback' => 'Excelente. Adaptar el mensaje a la audiencia es la esencia de la comunicación efectiva.'],
                            ['text' => 'Presento el informe técnico completo y explico los conceptos difíciles al cliente cuando lo pida.',
                             'points' => 18, 'feedback' => 'Buena iniciativa, aunque podrías anticiparte mejor a las necesidades del cliente.'],
                            ['text' => 'Hago una presentación general intermedia que cubra ambos públicos por igual.',
                             'points' => 10, 'feedback' => 'El enfoque intermedio suele no satisfacer plenamente a ninguna audiencia.'],
                            ['text' => 'Envío el reporte por correo y delego en el líder la presentación para ahorrar tiempo.',
                             'points' => 0,  'feedback' => 'Delegar sin preparar adecuadamente puede comprometer la relación con el cliente.'],
                        ],
                    ],
                    [
                        'scenario' => 'Durante una reunión de equipo, un colega interrumpe constantemente tus propuestas. ¿Cómo manejas la situación?',
                        'options'  => [
                            ['text' => 'Espero que termine, luego retomo con "Como mencionaba..." y solicito al moderador establecer turnos de palabra.',
                             'points' => 25, 'feedback' => 'Perfecto. Mantienes la calma y propones una solución estructural al problema.'],
                            ['text' => 'Le hablo en privado después de la reunión para expresarle cómo me afecta su conducta.',
                             'points' => 18, 'feedback' => 'Buen manejo. La retroalimentación privada es respetuosa y efectiva.'],
                            ['text' => 'Continúo hablando en voz más alta para recuperar la atención del grupo.',
                             'points' => 10, 'feedback' => 'Puede generar tensión innecesaria; es mejor buscar una estrategia más asertiva.'],
                            ['text' => 'Dejo de hablar y espero que la reunión termine para no generar conflicto.',
                             'points' => 0,  'feedback' => 'Evitar el problema no lo resuelve y afecta tu participación en el equipo.'],
                        ],
                    ],
                    [
                        'scenario' => 'Recibes un correo de un stakeholder con un malentendido grave sobre el alcance del proyecto. El correo fue copiado a 15 personas. ¿Qué haces?',
                        'options'  => [
                            ['text' => 'Llamo al stakeholder primero para alinear el mensaje, luego envío una respuesta clara al grupo con la información correcta y el documento de alcance.',
                             'points' => 25, 'feedback' => 'Ideal. Primero alineas en privado y luego comunicas formalmente al grupo.'],
                            ['text' => 'Respondes al grupo directamente con la corrección y el documento de alcance adjunto.',
                             'points' => 18, 'feedback' => 'Correcto, aunque coordinar primero con el stakeholder evita posibles fricciones.'],
                            ['text' => 'Solicitas una reunión urgente con todos los copiados para aclarar el malentendido.',
                             'points' => 10, 'feedback' => 'Una reunión es costosa en tiempo; a veces un correo bien redactado es más eficiente.'],
                            ['text' => 'Ignoras el correo esperando que se resuelva solo o que alguien más lo corrija.',
                             'points' => 0,  'feedback' => 'Los malentendidos sobre alcance no se resuelven solos y pueden escalar.'],
                        ],
                    ],
                    [
                        'scenario' => 'Debes dar retroalimentación constructiva a un compañero cuyo trabajo tiene errores importantes pero que se esforzó mucho. ¿Cómo lo abordas?',
                        'options'  => [
                            ['text' => 'Uso el método SBI (Situación-Comportamiento-Impacto): reconozco el esfuerzo, describo los errores específicos y propongo mejoras concretas.',
                             'points' => 25, 'feedback' => 'Excelente. El método SBI es una técnica probada de retroalimentación efectiva.'],
                            ['text' => 'Le digo que su trabajo tiene áreas de mejora y le señalo los errores directamente.',
                             'points' => 18, 'feedback' => 'Bien, aunque podría ser más estructurado para que la retroalimentación sea más efectiva.'],
                            ['text' => 'Primero lo elogias extensamente por el esfuerzo y al final mencionas los errores brevemente.',
                             'points' => 10, 'feedback' => 'El "sandwich de elogios" puede diluir el mensaje sobre los errores.'],
                            ['text' => 'Corriges el trabajo directamente sin decirle nada para no desmotivarlo.',
                             'points' => 0,  'feedback' => 'Privarlo de la retroalimentación impide su desarrollo profesional.'],
                        ],
                    ],
                    [
                        'scenario' => 'En una negociación con un proveedor, la comunicación se vuelve tensa. El proveedor eleva la voz. ¿Cuál es tu reacción?',
                        'options'  => [
                            ['text' => 'Mantengo la calma, bajo mi tono de voz deliberadamente y propongo una pausa de 5 minutos para que ambos podamos retomar con serenidad.',
                             'points' => 25, 'feedback' => 'Excelente control emocional. Bajar el tono propio es una técnica eficaz de desescalada.'],
                            ['text' => 'Mantengo mi posición con firmeza sin bajar la guardia, siendo asertivo pero respetuoso.',
                             'points' => 18, 'feedback' => 'Buena asertividad, aunque una pausa estratégica podría mejorar el resultado.'],
                            ['text' => 'Cedes en algunos puntos para calmar la situación y retomar después.',
                             'points' => 10, 'feedback' => 'Ceder bajo presión puede afectar el resultado final de la negociación.'],
                            ['text' => 'Elevas también la voz para que tu posición sea escuchada con igual fuerza.',
                             'points' => 0,  'feedback' => 'Escalar el tono empeora la negociación y daña la relación a largo plazo.'],
                        ],
                    ],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // COMPETENCIA 2: TRABAJO EN EQUIPO
            // ─────────────────────────────────────────────────────────────────
            'trabajo_equipo' => [
                'label'       => 'Trabajo en Equipo',
                'description' => 'Capacidad para colaborar eficazmente, construir relaciones de confianza y contribuir al logro de objetivos colectivos.',
                'icon'        => 'fas fa-users',
                'color'       => '#27ae60',
                'questions'   => [
                    [
                        'scenario' => 'Tu equipo tiene un plazo ajustado y un compañero está atrasado en su entregable crítico. ¿Qué haces?',
                        'options'  => [
                            ['text' => 'Me acerco al compañero, evalúo en qué puedo apoyarlo sin quitarle autonomía y redistribuimos tareas con el líder si es necesario.',
                             'points' => 25, 'feedback' => 'Perfecto. Combinas apoyo, respeto a la autonomía y visión sistémica del equipo.'],
                            ['text' => 'Lo ayudas directamente asumiendo parte de su trabajo para que el equipo cumpla el plazo.',
                             'points' => 18, 'feedback' => 'Buen espíritu de equipo, aunque es importante mantener la responsabilidad de cada rol.'],
                            ['text' => 'Alertas al líder del equipo sobre el retraso para que tome acción.',
                             'points' => 10, 'feedback' => 'Es válido escalar, pero primero podrías intentar apoyar directamente al compañero.'],
                            ['text' => 'Te concentras en tu propio trabajo; cada quien es responsable de sus tareas.',
                             'points' => 0,  'feedback' => 'El trabajo en equipo implica responsabilidad colectiva, no solo individual.'],
                        ],
                    ],
                    [
                        'scenario' => 'Surge un conflicto entre dos miembros del equipo que afecta el ambiente de trabajo. Tú no eres el líder. ¿Cómo actúas?',
                        'options'  => [
                            ['text' => 'Propongo una conversación mediada entre ambos, escuchando cada perspectiva y buscando puntos de acuerdo. Si no se resuelve, sugiero involucrar al líder.',
                             'points' => 25, 'feedback' => 'Excelente liderazgo situacional. Intervenir proactivamente como mediador es altamente valorado.'],
                            ['text' => 'Hablas con cada uno por separado para entender su perspectiva y los animas a resolverlo directamente.',
                             'points' => 18, 'feedback' => 'Buen enfoque. Escuchar a ambas partes es el primer paso para la resolución.'],
                            ['text' => 'Le informas al líder del conflicto para que él lo gestione formalmente.',
                             'points' => 10, 'feedback' => 'Es una opción válida, aunque podrías tomar más iniciativa antes de escalar.'],
                            ['text' => 'No te involucras porque no es tu rol y no quieres meterte en problemas ajenos.',
                             'points' => 0,  'feedback' => 'Los conflictos no gestionados se intensifican y afectan a todo el equipo.'],
                        ],
                    ],
                    [
                        'scenario' => 'Te asignan a un equipo multidisciplinario con personas de diferentes especialidades y culturas. ¿Cómo construyes confianza rápidamente?',
                        'options'  => [
                            ['text' => 'Propongo una sesión de kick-off donde cada miembro comparte sus fortalezas, expectativas y estilo de trabajo, y establecemos normas de equipo juntos.',
                             'points' => 25, 'feedback' => 'Excelente. Establecer normas compartidas desde el inicio crea cohesión rápida.'],
                            ['text' => 'Me presento, muestro interés genuino por los demás y demuestro competencia en mis entregas iniciales.',
                             'points' => 18, 'feedback' => 'Buena estrategia. La confianza se construye con palabras y actos consistentes.'],
                            ['text' => 'Esperas que el grupo se vaya conociendo naturalmente con el tiempo y las tareas.',
                             'points' => 10, 'feedback' => 'La confianza puede formarse, pero sin acelerarla se pierde tiempo valioso al inicio.'],
                            ['text' => 'Te enfocas en cumplir tus tareas excelentemente; el resto se dará solo.',
                             'points' => 0,  'feedback' => 'El rendimiento individual es necesario pero insuficiente para construir cohesión de equipo.'],
                        ],
                    ],
                    [
                        'scenario' => 'En una sesión de lluvia de ideas, tu propuesta es ignorada mientras que una propuesta inferior (en tu opinión) recibe todo el apoyo. ¿Cómo reaccionas?',
                        'options'  => [
                            ['text' => 'Pido la oportunidad de explicar mi propuesta con más detalle, destacando los beneficios que quizás no se han visualizado, y luego apoyo la decisión grupal.',
                             'points' => 25, 'feedback' => 'Perfecto. Defiendes tu idea con datos, luego te alineas con el equipo. Eso es madurez profesional.'],
                            ['text' => 'Apoyas la propuesta del grupo y buscas cómo incorporar elementos de tu idea en la implementación.',
                             'points' => 18, 'feedback' => 'Buena capacidad de adaptación. Aunque defender tu idea con datos también es válido.'],
                            ['text' => 'Aceptas la decisión en silencio pero externamente expresas tu desacuerdo con otros compañeros.',
                             'points' => 10, 'feedback' => 'Comentar el desacuerdo fuera del contexto puede crear fracturas en el equipo.'],
                            ['text' => 'Insistes repetidamente en tu propuesta hasta que el grupo reconsidere.',
                             'points' => 0,  'feedback' => 'La persistencia extrema puede percibirse como inflexibilidad y dañar la dinámica grupal.'],
                        ],
                    ],
                    [
                        'scenario' => 'Un integrante nuevo del equipo tiene dificultades para integrarse y su rendimiento está por debajo. ¿Qué haces?',
                        'options'  => [
                            ['text' => 'Me ofrezco voluntariamente como buddy/mentor, dedico tiempo para orientarlo y lo integro gradualmente a las dinámicas del equipo.',
                             'points' => 25, 'feedback' => 'Excelente. El mentoring proactivo acelera la integración y fortalece la cohesión del equipo.'],
                            ['text' => 'Le ofreces ayuda puntual cuando ves que tiene dificultades específicas.',
                             'points' => 18, 'feedback' => 'Buen apoyo reactivo. Un acompañamiento más sistemático podría ser aún más efectivo.'],
                            ['text' => 'Comentas la situación al líder para que tome las medidas de onboarding necesarias.',
                             'points' => 10, 'feedback' => 'Es válido, pero podrías complementarlo con tu apoyo directo al nuevo integrante.'],
                            ['text' => 'Esperas que se adapte solo; es su responsabilidad integrarse.',
                             'points' => 0,  'feedback' => 'La integración es una responsabilidad compartida. El equipo se beneficia cuando todos se apoyan.'],
                        ],
                    ],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // COMPETENCIA 3: RESOLUCIÓN DE PROBLEMAS
            // ─────────────────────────────────────────────────────────────────
            'resolucion_problemas' => [
                'label'       => 'Resolución de Problemas',
                'description' => 'Capacidad para analizar situaciones complejas, identificar causas raíz y generar soluciones efectivas bajo presión.',
                'icon'        => 'fas fa-puzzle-piece',
                'color'       => '#8e44ad',
                'questions'   => [
                    [
                        'scenario' => 'En plena ejecución de un proyecto crítico, descubres que un insumo clave no llegará a tiempo y podría paralizar las obras. ¿Qué haces?',
                        'options'  => [
                            ['text' => 'Activo el plan de contingencia: contacto proveedores alternativos, reordeno las actividades del cronograma para trabajar lo que no depende del insumo y comunico el riesgo inmediatamente.',
                             'points' => 25, 'feedback' => 'Perfecto. Actuar en múltiples frentes simultáneamente es la respuesta ideal ante una crisis.'],
                            ['text' => 'Contactas urgentemente al proveedor original para acelerar la entrega y buscas un proveedor alternativo como respaldo.',
                             'points' => 18, 'feedback' => 'Buen enfoque. Agregar la reordenación de actividades haría la respuesta más completa.'],
                            ['text' => 'Informas al cliente y al jefe de proyecto y esperas instrucciones para proceder.',
                             'points' => 10, 'feedback' => 'Escalar es necesario, pero en paralelo deberías ya estar explorando alternativas.'],
                            ['text' => 'Esperas a ver si el insumo llega en los próximos días antes de tomar medidas.',
                             'points' => 0,  'feedback' => 'Esperar sin actuar en una situación crítica puede comprometer el proyecto completo.'],
                        ],
                    ],
                    [
                        'scenario' => 'Tienes 3 problemas urgentes simultáneos con recursos limitados. ¿Cómo decides cuál atender primero?',
                        'options'  => [
                            ['text' => 'Evalúo impacto (costo, plazo, seguridad) y urgencia de cada problema usando una matriz de priorización, luego asigno recursos según el análisis.',
                             'points' => 25, 'feedback' => 'Excelente. La priorización basada en impacto objetivo evita decisiones emocionales bajo presión.'],
                            ['text' => 'Atiendes primero el que tiene mayor impacto en el cronograma general del proyecto.',
                             'points' => 18, 'feedback' => 'Buena lógica. Considerar también el impacto en seguridad o costo podría ajustar la prioridad.'],
                            ['text' => 'Distribuyes los recursos equitativamente entre los 3 problemas para avanzar en todos.',
                             'points' => 10, 'feedback' => 'Dividir recursos puede resultar en que ninguno se resuelva óptimamente.'],
                            ['text' => 'Atacas el más fácil primero para liberar recursos rápido y luego los otros.',
                             'points' => 0,  'feedback' => 'Resolver lo más fácil primero puede dejar los problemas de mayor impacto sin atención suficiente.'],
                        ],
                    ],
                    [
                        'scenario' => 'Un proceso que funcionó bien por meses empieza a fallar repetidamente. ¿Cómo identificas la causa raíz?',
                        'options'  => [
                            ['text' => 'Aplico la técnica de los 5 Porqués o un diagrama de Ishikawa con el equipo para encontrar la causa raíz sistémica, no solo el síntoma visible.',
                             'points' => 25, 'feedback' => 'Excelente. El análisis de causa raíz evita soluciones paliativas que no resuelven el fondo.'],
                            ['text' => 'Revisas los cambios recientes en el proceso o entorno para identificar qué variable cambió.',
                             'points' => 18, 'feedback' => 'Buena táctica investigativa. Combinarlo con el análisis causal lo haría más robusto.'],
                            ['text' => 'Aplicas la solución que funcionó la última vez que falló algo similar.',
                             'points' => 10, 'feedback' => 'Las soluciones anteriores son un punto de partida, pero pueden no aplicar al nuevo contexto.'],
                            ['text' => 'Implementas un parche rápido para que funcione y asignas a alguien a investigar más tarde.',
                             'points' => 0,  'feedback' => 'Los parches sin análisis de causa raíz generan deuda técnica y recurrencia del problema.'],
                        ],
                    ],
                    [
                        'scenario' => 'Tu propuesta de solución a un problema fue rechazada por el cliente. ¿Cómo avanzas?',
                        'options'  => [
                            ['text' => 'Indago profundamente los criterios del cliente para el rechazo, reformulo la propuesta incorporando sus objeciones y presento alternativas con análisis de pro/contra.',
                             'points' => 25, 'feedback' => 'Perfecto. Entender el "por qué" del rechazo es fundamental para mejorar la solución.'],
                            ['text' => 'Presentas una propuesta alternativa que considerabas como segunda opción.',
                             'points' => 18, 'feedback' => 'Buena agilidad. Entender primero el motivo del rechazo haría la alternativa más asertiva.'],
                            ['text' => 'Solicitas al cliente que especifique qué esperaría de una solución aceptable.',
                             'points' => 10, 'feedback' => 'Buena práctica, aunque es más efectivo llegar con opciones concretas ya analizadas.'],
                            ['text' => 'Insistes en que tu propuesta es la correcta y presentas más argumentos técnicos para convencerlo.',
                             'points' => 0,  'feedback' => 'Insistir sin escuchar las necesidades del cliente erosiona la relación de confianza.'],
                        ],
                    ],
                    [
                        'scenario' => 'Descubres que una decisión tomada por tu equipo hace 3 semanas fue incorrecta y causará problemas costosos. ¿Qué haces?',
                        'options'  => [
                            ['text' => 'Documento el problema, analizo el impacto, propongo un plan correctivo con opciones y lo presento proactivamente al equipo y stakeholders antes de que el problema escale.',
                             'points' => 25, 'feedback' => 'Excelente gestión proactiva. Reportar errores con soluciones es una señal de madurez profesional.'],
                            ['text' => 'Informas inmediatamente al líder con el análisis del problema y tus recomendaciones.',
                             'points' => 18, 'feedback' => 'Correcto. Escalar rápido con análisis es mucho mejor que esperar.'],
                            ['text' => 'Intentas corregir el problema silenciosamente sin involucrar a otros para no generar alarma.',
                             'points' => 10, 'feedback' => 'Corregir solo puede ser insuficiente. Los stakeholders deben estar informados de impactos relevantes.'],
                            ['text' => 'Esperas a ver si realmente genera problemas antes de alarmar a todos innecesariamente.',
                             'points' => 0,  'feedback' => 'Esperar pasivamente ante un problema conocido es una omisión profesionalmente inaceptable.'],
                        ],
                    ],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // COMPETENCIA 4: ADAPTABILIDAD
            // ─────────────────────────────────────────────────────────────────
            'adaptabilidad' => [
                'label'       => 'Adaptabilidad',
                'description' => 'Capacidad para responder positivamente ante el cambio, aprender continuamente y mantener el rendimiento en entornos de alta incertidumbre.',
                'icon'        => 'fas fa-sync-alt',
                'color'       => '#e67e22',
                'questions'   => [
                    [
                        'scenario' => 'Tu empresa adopta una nueva metodología de gestión (ej. NEC4 en lugar de la metodología tradicional). Tu rol cambiaría significativamente. ¿Cómo reaccionas?',
                        'options'  => [
                            ['text' => 'Me informo profundamente sobre la nueva metodología, identifico cómo mis habilidades actuales se transfieren y me ofrezco como parte del equipo piloto de implementación.',
                             'points' => 25, 'feedback' => 'Excelente. Convertirse en agente del cambio en lugar de resistirlo es la actitud más valiosa.'],
                            ['text' => 'Asistes a todas las capacitaciones disponibles y aprendes la nueva metodología con una mente abierta.',
                             'points' => 18, 'feedback' => 'Muy buena disposición. Ofrecerte como piloto lo llevaría al siguiente nivel.'],
                            ['text' => 'Aceptas el cambio pero expresas tus preocupaciones al líder sobre el impacto en los procesos actuales.',
                             'points' => 10, 'feedback' => 'Es válido expresar preocupaciones, aunque el tono proactivo marca la diferencia.'],
                            ['text' => 'Prefieres esperar a ver cómo les va a los pioneros antes de comprometerte con el cambio.',
                             'points' => 0,  'feedback' => 'La espera pasiva ante el cambio organizacional reduce tu relevancia y empleabilidad.'],
                        ],
                    ],
                    [
                        'scenario' => 'En medio de un proyecto, el cliente cambia el alcance significativamente sin aumentar presupuesto ni plazo. ¿Cómo lo manejas?',
                        'options'  => [
                            ['text' => 'Analizo el impacto del cambio en costo, plazo y calidad, presento las opciones al cliente (aceptar con ajustes, priorizar dentro del presupuesto o diferir) y negocio el camino a seguir.',
                             'points' => 25, 'feedback' => 'Perfecto. Adaptarse a los cambios sin perder el control del proyecto es la habilidad clave.'],
                            ['text' => 'Replanteas con el cliente qué elementos del alcance original pueden reducirse para acomodar los cambios nuevos.',
                             'points' => 18, 'feedback' => 'Buena negociación. Presentar el análisis de impacto completo fortalece la conversación.'],
                            ['text' => 'Absorbes los cambios en el equipo, ajustas las prioridades y haces lo posible dentro del presupuesto.',
                             'points' => 10, 'feedback' => 'Absorber cambios sin documentarlos puede generar problemas de calidad y burnout del equipo.'],
                            ['text' => 'Rechazas los cambios indicando que el contrato original no los contempla.',
                             'points' => 0,  'feedback' => 'Rechazar de plano sin explorar alternativas daña la relación con el cliente.'],
                        ],
                    ],
                    [
                        'scenario' => 'Te asignan a un proyecto en un sector que desconoces completamente con un plazo de arranque muy corto. ¿Qué haces?',
                        'options'  => [
                            ['text' => 'Trazo un plan de aprendizaje acelerado: estudio referencias clave del sector, identifico expertos internos para consultar y establezco hitos de aprendizaje junto con los del proyecto.',
                             'points' => 25, 'feedback' => 'Excelente. El aprendizaje estructurado y acelerado es la respuesta ideal a la ignorancia intencionada.'],
                            ['text' => 'Aceptas el reto, estudias lo más que puedes antes del arranque y aprendes en el camino.',
                             'points' => 18, 'feedback' => 'Buena disposición. Un plan de aprendizaje más estructurado aumentaría tu efectividad.'],
                            ['text' => 'Solicitas al líder un período de inducción formal antes de comenzar a trabajar en el proyecto.',
                             'points' => 10, 'feedback' => 'La inducción formal es válida, aunque podrías iniciar el aprendizaje autónomo en paralelo.'],
                            ['text' => 'Comunicas al líder que no tienes la experiencia necesaria y solicitas ser reasignado a un proyecto más afín.',
                             'points' => 0,  'feedback' => 'Negarse a salir de la zona de confort limita significativamente el crecimiento profesional.'],
                        ],
                    ],
                    [
                        'scenario' => 'Tu equipo enfrenta una crisis que invalida el 60% del trabajo realizado. El ambiente es de desánimo. ¿Cómo contribuyes?',
                        'options'  => [
                            ['text' => 'Propongo una sesión de "reset": reconocemos el impacto, identificamos las lecciones aprendidas y reencuadramos el avance restante como base sólida para reconstruir más inteligentemente.',
                             'points' => 25, 'feedback' => 'Excelente. La resiliencia colectiva se construye con reencuadre positivo y acción inmediata.'],
                            ['text' => 'Mantienes una actitud positiva y enfocada en soluciones, lo que contagia al resto del equipo.',
                             'points' => 18, 'feedback' => 'Muy bueno. El liderazgo emocional positivo es poderoso en momentos de crisis.'],
                            ['text' => 'Te concentras en tu parte del trabajo y tratas de mantenerte productivo a pesar del ambiente.',
                             'points' => 10, 'feedback' => 'La productividad individual ayuda, pero el equipo necesita energía colectiva en una crisis.'],
                            ['text' => 'Compartes la frustración del equipo; es normal sentirse así y no se puede fingir que no pasó nada.',
                             'points' => 0,  'feedback' => 'Reconocer la frustración es válido, pero líderes y colaboradores también deben movilizar hacia la solución.'],
                        ],
                    ],
                    [
                        'scenario' => 'Te informan que a partir del próximo mes usarás una herramienta digital que no conoces y que reemplazará tu flujo de trabajo actual. ¿Cómo te preparas?',
                        'options'  => [
                            ['text' => 'Busco tutoriales, documentación oficial y usuarios avanzados de la herramienta, practica antes del cambio y propongo sesiones de transferencia de conocimiento para el equipo.',
                             'points' => 25, 'feedback' => 'Excelente. Prepararse con anticipación y compartir el conocimiento es el estándar profesional.'],
                            ['text' => 'Completas todos los cursos y recursos de capacitación disponibles antes del cambio.',
                             'points' => 18, 'feedback' => 'Muy buena disposición. Proponer sesiones de equipo lo convierte en un aporte colectivo.'],
                            ['text' => 'Esperas la capacitación formal que la empresa proveerá antes del cambio.',
                             'points' => 10, 'feedback' => 'La capacitación formal es útil, pero la preparación autónoma te da ventaja.'],
                            ['text' => 'Aprendes la herramienta en el momento en que tengas que usarla; es la forma más eficiente.',
                             'points' => 0,  'feedback' => 'Aprender bajo presión en producción aumenta el riesgo de errores y reduce la calidad del trabajo.'],
                        ],
                    ],
                ],
            ],
        ];
    }
}

