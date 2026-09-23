<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Security;
use App\Models\SoftSkillsModel;

/**
 * PMO SOLUTIONS — Controlador de Evaluación de Habilidades Blandas
 *
 * Gestiona el flujo completo de evaluación situacional:
 * - Renderiza el formulario interactivo de evaluación
 * - Procesa las respuestas y calcula el score
 * - Persiste la evaluación y retorna el reporte JSON
 * - Permite consulta y descarga de reportes individuales
 *
 * Rutas:
 *   GET  /evaluacion-habilidades                    → Formulario de evaluación
 *   POST /api/evaluacion-habilidades                → Procesar evaluación
 *   GET  /api/evaluacion-habilidades/{codigo}       → Obtener reporte por código
 *   GET  /api/evaluacion-habilidades/catalog        → Catálogo de competencias
 *   GET  /evaluacion-habilidades/resultados         → Listado paginado (admin)
 */
class SoftSkillsController extends Controller {

    private SoftSkillsModel $model;

    public function __construct() {
        parent::__construct();
        $this->model = new SoftSkillsModel();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VISTAS HTML
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Renderiza la página de evaluación interactiva de habilidades blandas.
     */
    public function index(): void {
        $catalog = $this->model->getCatalog();

        $this->render('evaluacion-habilidades', [
            'pageTitle'       => 'Evaluación de Habilidades Blandas | PMO Solutions',
            'metaDescription' => 'Evalúa tus competencias blandas: comunicación, trabajo en equipo, resolución de problemas y adaptabilidad. Recibe un reporte personalizado con recomendaciones.',
            'activeNav'       => 'capacitaciones',
            'catalog'         => $catalog,
            'total_questions' => count($catalog) * SoftSkillsModel::QUESTIONS_PER_COMPETENCY,
            'extraJs'         => [
                ['src' => '/js/modules/soft-skills.js', 'type' => 'module'],
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENDPOINTS API JSON
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /api/evaluacion-habilidades
     * Procesa las respuestas de la evaluación, calcula scores y retorna el reporte.
     *
     * Body JSON esperado:
     * {
     *   "nombre_completo": "Juan Pérez",
     *   "email": "juan@empresa.com",
     *   "cargo": "Jefe de Proyectos",
     *   "empresa": "Constructora XYZ",
     *   "tipo_evaluacion": "Individual",
     *   "answers": {
     *     "comunicacion": {"1": 3, "2": 2, "3": 4, "4": 1, "5": 3},
     *     "trabajo_equipo": {"1": 4, ...},
     *     "resolucion_problemas": {"1": 2, ...},
     *     "adaptabilidad": {"1": 4, ...}
     *   },
     *   "website_hp": ""
     * }
     */
    public function submit(): void {
        Security::initHeaders($this->config['security']['allowed_origins'] ?? []);
        Security::requireMethod('POST');

        // 1. Rate limiting inicial: registra y consume el intento inmediatamente después del método HTTP
        if (!Security::checkRateLimit('soft_skills_eval')) {
            $this->json(false, 'Demasiadas solicitudes. Por favor espera unos minutos antes de intentarlo nuevamente.', [], 429);
        }

        // 2. Control de Content-Type exacto (HTTP 415 Unsupported Media Type)
        if (!Security::requireJsonContentType()) {
            $this->json(false, 'El formato de contenido debe ser application/json.', [], 415);
        }

        // 3. Control de tamaño y profundidad (64 KB max, 5 niveles max)
        $data = Security::getRequestData(65536, 5);

        // Control de Payload Too Large (HTTP 413)
        if (Security::hasPayloadTooLarge()) {
            $this->json(false, 'El cuerpo de la solicitud excede el tamaño máximo permitido (64 KB).', [], 413);
        }

        // Control de profundidad excesiva de JSON (HTTP 400)
        if (Security::hasJsonDepthExceeded()) {
            $this->json(false, 'La estructura del JSON excede la profundidad máxima permitida.', [], 400);
        }

        // Verificar si hubo error de sintaxis en el payload JSON (HTTP 400 Bad Request)
        if (Security::hasJsonSyntaxError()) {
            $this->json(false, 'Cuerpo de la solicitud JSON sintácticamente inválido o corrupto.', [], 400);
        }

        // 4. Validación de Origen y Protección CSRF obligatoria (los fallos ya fueron contabilizados en el rate limiter)
        Csrf::requireValidRequest($data, $this->config['security']['allowed_origins'] ?? []);

        // 5. Honeypot anti-spam
        if (!Security::checkHoneypot($data)) {
            $this->json(false, 'Solicitud inválida.', [], 400);
        }

        // 6. Anti-Tampering: Rechazar con HTTP 422 si el cliente inyectó scores, niveles o resultados precalculados
        $forbiddenKeys = [
            'score', 'scores', 'puntaje', 'puntajes', 'resultado', 'resultados',
            'nivel_madurez', 'maturity_level', 'points', 'percentage', 'global_score',
            'feedback', 'details', 'strengths', 'areas_to_improve', 'recommendations'
        ];
        $presentForbidden = array_intersect(array_keys($data), $forbiddenKeys);
        if (!empty($presentForbidden)) {
            $this->json(false, 'No se permite enviar puntajes, resultados o niveles precalculados desde el cliente.', [
                'forbidden_fields' => array_values($presentForbidden)
            ], 422);
        }

        // 7. Validar tipos de datos escalares para campos del participante
        foreach (['nombre_completo', 'email', 'cargo', 'empresa', 'tipo_evaluacion'] as $strField) {
            if (isset($data[$strField]) && !is_string($data[$strField])) {
                $this->json(false, "El campo '{$strField}' debe ser una cadena de texto.", [], 422);
            }
        }

        // Validar campos obligatorios del participante
        $requiredFields = [
            'nombre_completo' => 'Nombre Completo',
            'email'           => 'Correo Electrónico',
        ];

        $validationErrors = Security::validateRequired($data, $requiredFields);

        if (!empty($validationErrors)) {
            $this->json(false, 'Por favor completa todos los campos obligatorios.', [
                'errors' => $validationErrors,
            ], 422);
        }

        $rawNombre   = trim((string)($data['nombre_completo'] ?? ''));
        $rawEmail    = trim((string)($data['email'] ?? ''));
        $rawCargo    = trim((string)($data['cargo'] ?? ''));
        $rawEmpresa  = trim((string)($data['empresa'] ?? ''));

        // Validar longitud mínima/máxima de campos sin recorte silencioso
        if (mb_strlen($rawNombre, 'UTF-8') < 3) {
            $this->json(false, 'El nombre completo debe tener al menos 3 caracteres.', [], 422);
        }
        if (mb_strlen($rawNombre, 'UTF-8') > 200) {
            $this->json(false, 'El nombre completo no debe exceder los 200 caracteres.', [], 422);
        }

        if (!Security::validateEmail($rawEmail) || mb_strlen($rawEmail, 'UTF-8') < 5) {
            $this->json(false, 'El correo electrónico ingresado no es válido.', [], 422);
        }
        if (mb_strlen($rawEmail, 'UTF-8') > 150) {
            $this->json(false, 'El correo electrónico no debe exceder los 150 caracteres.', [], 422);
        }

        if ($rawCargo !== '' && mb_strlen($rawCargo, 'UTF-8') > 100) {
            $this->json(false, 'El cargo no debe exceder los 100 caracteres.', [], 422);
        }

        if ($rawEmpresa !== '' && mb_strlen($rawEmpresa, 'UTF-8') > 150) {
            $this->json(false, 'La empresa no debe exceder los 150 caracteres.', [], 422);
        }

        // 8. Validación estricta de respuestas contra el catálogo oficial del backend
        $answersValidation = $this->model->validateAnswersStrict($data['answers'] ?? null);
        if (!$answersValidation['valid']) {
            $this->json(false, 'Las respuestas enviadas no son válidas o no corresponden al catálogo oficial.', [
                'errors' => $answersValidation['errors']
            ], 422);
        }

        $cleanAnswers = $answersValidation['cleanAnswers'];

        // Sanitizar datos del participante
        $participantData = [
            'nombre_completo' => Security::cleanString($rawNombre, 200),
            'email'           => Security::cleanEmail($rawEmail),
            'cargo'           => Security::cleanString($rawCargo, 100),
            'empresa'         => Security::cleanString($rawEmpresa, 150),
            'tipo_evaluacion' => $this->sanitizeTipoEvaluacion($data['tipo_evaluacion'] ?? 'Individual'),
        ];

        // Generar código único de evaluación y token de acceso de alta entropía (32 bytes = 64 hex)
        $codigoEvaluacion = Security::generateClaimCode('EVA');
        $rawToken         = Security::generateReportToken();
        $tokenHash        = Security::hashReportToken($rawToken);

        // Generar reporte completo con cálculo 100% en el servidor
        $report = $this->model->generateReport(
            $codigoEvaluacion,
            $participantData,
            $cleanAnswers
        );

        // Persistir en DB (si disponible) — graceful degradation con token hash interno
        $evaluationId = $this->model->saveEvaluation(
            $report,
            $participantData,
            Security::getClientIp(),
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $tokenHash
        );

        $dbSaved = ($evaluationId !== false);

        // Sanitizar el reporte inmediato para la vista del participante (sin email ni datos privados)
        $publicReport = $report;
        if (isset($publicReport['participant']['email'])) {
            unset($publicReport['participant']['email']);
        }

        // Respuesta inmediata al participante: sin token, sin evaluation_id y sin consulta posterior
        $responseData = [
            'codigo_evaluacion'          => $codigoEvaluacion,
            'report_retrieval_available' => false,
            'report'                     => $publicReport,
            'db_saved'                   => $dbSaved,
        ];

        $this->json(true, 'Evaluación completada exitosamente.', $responseData, 201);
    }

    /**
     * GET /api/evaluacion-habilidades/{codigo}
     * Consulta posterior deshabilitada en esta versión (responde 404).
     *
     * @param string $codigo Código EVA-2026-XXXXX
     */
    public function getReport(string $codigo): void {
        Security::initHeaders($this->config['security']['allowed_origins'] ?? []);

        // Si la funcionalidad de consulta posterior está deshabilitada por configuración, emitir 404
        if (empty($this->config['features']['report_retrieval_enabled'])) {
            Security::dummyHashEquals();
            Security::sendIndistinguishable404();
        }

        // Rate limiting: máximo 15 consultas por IP cada 5 minutos (300s)
        $actionKey = 'report_' . Security::getClientIp();
        if (!Security::checkRateLimit($actionKey, 15, 300)) {
            $retryAfter = Security::getLastRetryAfter();
            header("Retry-After: {$retryAfter}");
            $this->json(false, "Demasiadas consultas de reportes. Por favor espera {$retryAfter} segundos antes de volver a consultar.", [
                'retry_after' => $retryAfter
            ], 429);
        }

        // Sanitizar y validar formato del código
        $codigo = strtoupper(preg_replace('/[^A-Z0-9\-]/', '', $codigo));
        if (!preg_match('/^EVA-\d{4}-[A-Z0-9]{5}$/', $codigo)) {
            Security::dummyHashEquals();
            Security::sendIndistinguishable404();
        }

        // 1. Si existe sesión administrativa activa, permitir acceso directo sin token
        $isAdmin = Security::isAuthenticated() && Security::hasRole('admin');

        if (!$isAdmin) {
            // 2. Extraer y validar el token de autorización
            $token = Security::extractReportToken();

            if (empty($token) || !Security::isValidReportTokenFormat($token)) {
                Security::dummyHashEquals();
                Security::sendIndistinguishable404();
            }

            // 3. Consultar directamente a la base de datos (sin caché) el hash almacenado
            $authData = $this->model->getAuthHashByCode($codigo);

            if (!$authData || empty($authData['report_access_token_hash'])) {
                // Código inexistente o registro legacy sin token
                Security::dummyHashEquals();
                Security::sendIndistinguishable404();
            }

            // 4. Comparación en tiempo constante del hash del token provisto
            $providedHash = Security::hashReportToken($token);
            if (!hash_equals($authData['report_access_token_hash'], $providedHash)) {
                Security::sendIndistinguishable404();
            }
        }

        // 5. Obtener el DTO público minimizado (sin datos sensibles ni material de autorización)
        $publicReport = $this->model->getPublicReport($codigo);

        if (!$publicReport) {
            Security::sendIndistinguishable404();
        }

        // Cabecera anti-caché para reportes personales
        header('Cache-Control: no-store, private');

        $this->json(true, 'Evaluación encontrada.', [
            'evaluation' => $publicReport,
        ]);
    }

    /**
     * GET /api/evaluacion-habilidades/catalog
     * Retorna el catálogo de competencias (para renderizado dinámico en el frontend).
     */
    public function getCatalog(): void {
        Security::initHeaders($this->config['security']['allowed_origins'] ?? []);

        $catalog = $this->model->getCatalog();

        // Retornar solo metadata (sin las opciones con puntajes)
        $publicCatalog = array_map(function ($competency) {
            return [
                'label'            => $competency['label'],
                'description'      => $competency['description'],
                'icon'             => $competency['icon'],
                'color'            => $competency['color'],
                'question_count'   => count($competency['questions']),
                'questions'        => array_map(fn($q) => [
                    'scenario'     => $q['scenario'],
                    'options'      => array_map(fn($o) => ['text' => $o['text']], $q['options']),
                ], $competency['questions']),
            ];
        }, $catalog);

        // Cache público de 1 hora (header HTTP)
        header('Cache-Control: public, max-age=3600');
        header('ETag: "softskills-catalog-v1"');

        $this->json(true, 'Catálogo de competencias.', [
            'catalog'     => $publicCatalog,
            'total_questions' => count($catalog) * SoftSkillsModel::QUESTIONS_PER_COMPETENCY,
            'maturity_levels' => SoftSkillsModel::MATURITY_LEVELS,
        ]);
    }

    /**
     * GET /evaluacion-habilidades/resultados
     * Administración deshabilitada en esta versión (responde 404).
     */
    public function listResults(): void {
        Security::initHeaders($this->config['security']['allowed_origins'] ?? []);

        // Si la administración está deshabilitada, responder 404 sin revelar el endpoint
        if (empty($this->config['features']['admin_enabled'])) {
            Security::sendIndistinguishable404();
        }

        // Control de acceso RBAC: requiere rol 'admin'
        Security::requireRole('admin');

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(50, max(1, (int) ($_GET['per_page'] ?? 20)));

        $results = $this->model->listPaginated($page, $perPage);

        $this->json(true, 'Listado de evaluaciones.', $results);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Sanitiza y valida el tipo de evaluación.
     */
    private function sanitizeTipoEvaluacion(string $tipo): string {
        $allowed = ['Individual', 'Grupal', 'Pre-Capacitación', 'Post-Capacitación'];
        return in_array($tipo, $allowed, true) ? $tipo : 'Individual';
    }
}

