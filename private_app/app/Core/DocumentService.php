<?php
namespace App\Core;

/**
 * PMO SOLUTIONS - Servicio de Validación y Consulta de Identidad (DocumentService)
 * 
 * Gestiona:
 * 1. Validación algorítmica y matemática nativa (Algoritmo Módulo 11 SUNAT para RUC, RENIEC DNI, CE y Pasaporte).
 * 2. Consulta y autocompletado opcional mediante APIs peruanas gratuitas (Decolecta, ApiPeru.dev, ApisPeru).
 * 3. Fallback inteligente y seguro a modo manual en caso de indisponibilidad o sin token.
 */
class DocumentService {

    /**
     * Valida el formato y la integridad algorítmica de un documento peruano
     */
    public static function validate(string $tipo, string $numero): array {
        $tipo = trim($tipo);
        $num = preg_replace('/\s+/', '', trim($numero));

        if ($tipo === 'CE') {
            $tipo = 'Carnet de Extranjería';
        }

        switch ($tipo) {
            case 'DNI':
                return self::validateDni($num);
            case 'RUC':
                return self::validateRuc($num);
            case 'Carnet de Extranjería':
                return self::validateCarnetExtranjeria($num);
            case 'Pasaporte':
                return self::validatePasaporte($num);
            default:
                return [
                    'valid'   => false,
                    'message' => 'Tipo de documento no soportado.'
                ];
        }
    }

    /**
     * Valida DNI peruano (8 dígitos numéricos y descarte de secuencias inválidas)
     */
    public static function validateDni(string $dni): array {
        if (!preg_match('/^\d{8}$/', $dni)) {
            return [
                'valid'   => false,
                'message' => 'El DNI debe contener exactamente 8 dígitos numéricos.'
            ];
        }

        // Descartar secuencias repetitivas o números de prueba ficticios
        $invalidPatterns = [
            '00000000', '11111111', '22222222', '33333333', '44444444',
            '55555555', '66666666', '77777777', '88888888', '99999999',
            '12345678', '87654321'
        ];

        if (in_array($dni, $invalidPatterns, true)) {
            return [
                'valid'   => false,
                'message' => 'El número de DNI ingresado no es válido.'
            ];
        }

        return [
            'valid'   => true,
            'message' => 'DNI válido.'
        ];
    }

    /**
     * Valida RUC peruano con prefijos oficiales y Algoritmo Módulo 11 de la SUNAT
     */
    public static function validateRuc(string $ruc): array {
        if (!preg_match('/^\d{11}$/', $ruc)) {
            return [
                'valid'   => false,
                'message' => 'El RUC debe contener exactamente 11 dígitos numéricos.'
            ];
        }

        // 1. Validar prefijos oficiales de la SUNAT
        $prefix = substr($ruc, 0, 2);
        $validPrefixes = ['10', '15', '17', '20'];
        if (!in_array($prefix, $validPrefixes, true)) {
            return [
                'valid'   => false,
                'message' => 'El RUC debe iniciar con 10, 15, 17 o 20 conforme a la normativa de SUNAT.'
            ];
        }

        // 2. Algoritmo SUNAT - Módulo 11 para verificar el dígito verificador
        $factors = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += ((int)$ruc[$i]) * $factors[$i];
        }

        $remainder = $sum % 11;
        $checkDigit = 11 - $remainder;
        if ($checkDigit === 10) {
            $checkDigit = 0;
        } elseif ($checkDigit === 11) {
            $checkDigit = 1;
        }

        if ($checkDigit !== (int)$ruc[10]) {
            return [
                'valid'   => false,
                'message' => 'El RUC ingresado no cumple con el dígito verificador de SUNAT (número no auténtico).'
            ];
        }

        return [
            'valid'   => true,
            'message' => 'RUC válido.'
        ];
    }

    /**
     * Valida Carnet de Extranjería (Migraciones Perú)
     */
    public static function validateCarnetExtranjeria(string $ce): array {
        // Estándar oficial de Migraciones: 8 a 12 caracteres alfanuméricos
        if (!preg_match('/^[a-zA-Z0-9]{8,12}$/', $ce)) {
            return [
                'valid'   => false,
                'message' => 'El Carnet de Extranjería debe contener entre 8 y 12 caracteres alfanuméricos.'
            ];
        }

        return [
            'valid'   => true,
            'message' => 'Carnet de Extranjería válido.'
        ];
    }

    /**
     * Valida Pasaporte
     */
    public static function validatePasaporte(string $pass): array {
        if (!preg_match('/^[a-zA-Z0-9]{6,15}$/', $pass)) {
            return [
                'valid'   => false,
                'message' => 'El pasaporte debe contener entre 6 y 15 caracteres alfanuméricos.'
            ];
        }

        return [
            'valid'   => true,
            'message' => 'Pasaporte válido.'
        ];
    }

    /**
     * Consulta opcional a APIs públicas peruanas si existe un token configurado en .env
     * Si no hay token o la API falla, retorna gracefully indicando modo manual.
     */
    public static function lookup(string $tipo, string $numero): array {
        $val = self::validate($tipo, $numero);
        if (!$val['valid']) {
            return [
                'success' => false,
                'mode'    => 'invalid',
                'message' => $val['message']
            ];
        }

        // Si el tipo es Pasaporte o CE y no es soportado por el proveedor estándar, retornar validación matemática
        if (!in_array($tipo, ['DNI', 'RUC'], true)) {
            return [
                'success'    => true,
                'mode'       => 'math_valid',
                'message'    => $val['message'],
                'data'       => [
                    'tipo_documento'   => $tipo,
                    'numero_documento' => $numero
                ]
            ];
        }

        $token = Env::string('PMO_DOC_API_TOKEN', '');
        $provider = strtolower(Env::string('PMO_DOC_API_PROVIDER', 'decolecta')); // 'decolecta', 'apiperu', 'apisperu'

        if (empty($token)) {
            return [
                'success'    => true,
                'mode'       => 'math_valid',
                'message'    => 'Documento verificado matemáticamente. Ingrese los datos manualmente.',
                'data'       => [
                    'tipo_documento'   => $tipo,
                    'numero_documento' => $numero
                ]
            ];
        }

        $result = self::queryProvider($provider, $token, $tipo, $numero);
        if ($result['success']) {
            return [
                'success' => true,
                'mode'    => 'api_found',
                'message' => 'Datos obtenidos exitosamente.',
                'data'    => $result['data']
            ];
        }

        // Fallback transparente
        return [
            'success'    => true,
            'mode'       => 'fallback_manual',
            'message'    => 'Verificado. Complete los nombres manualmente.',
            'data'       => [
                'tipo_documento'   => $tipo,
                'numero_documento' => $numero
            ]
        ];
    }

    /**
     * Realiza la llamada HTTP segura a la API externa
     */
    private static function queryProvider(string $provider, string $token, string $tipo, string $numero): array {
        $url = '';
        $headers = [
            'Accept: application/json',
            'User-Agent: PMOSolutions/1.0'
        ];

        if ($provider === 'decolecta') {
            $headers[] = 'Authorization: Bearer ' . $token;
            if ($tipo === 'DNI') {
                $url = "https://api.decolecta.com/v1/reniec/dni?numero=" . urlencode($numero);
            } else {
                $url = "https://api.decolecta.com/v1/sunat/ruc?numero=" . urlencode($numero);
            }
        } elseif ($provider === 'apiperu') {
            $headers[] = 'Authorization: Bearer ' . $token;
            if ($tipo === 'DNI') {
                $url = "https://apiperu.dev/api/dni/" . urlencode($numero);
            } else {
                $url = "https://apiperu.dev/api/ruc/" . urlencode($numero);
            }
        } elseif ($provider === 'apisperu') {
            if ($tipo === 'DNI') {
                $url = "https://dniruc.apisperu.com/api/v1/dni/" . urlencode($numero) . "?token=" . urlencode($token);
            } else {
                $url = "https://dniruc.apisperu.com/api/v1/ruc/" . urlencode($numero) . "?token=" . urlencode($token);
            }
        } else {
            return ['success' => false];
        }

        if (!function_exists('curl_init')) {
            return ['success' => false];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200 || empty($response)) {
            return ['success' => false];
        }

        $json = json_decode($response, true);
        if (!is_array($json)) {
            return ['success' => false];
        }

        return self::normalizeApiResponse($provider, $tipo, $json);
    }

    /**
     * Normaliza la respuesta según el proveedor a un formato unificado
     */
    private static function normalizeApiResponse(string $provider, string $tipo, array $json): array {
        $nombre = '';
        $domicilio = '';

        if ($provider === 'decolecta') {
            if ($tipo === 'DNI') {
                $d = $json['data'] ?? $json;
                $nombres = $d['nombres'] ?? $d['first_name'] ?? '';
                $apPaterno = $d['apellido_paterno'] ?? $d['paternal_surname'] ?? '';
                $apMaterno = $d['apellido_materno'] ?? $d['maternal_surname'] ?? '';
                $nombre = trim("{$nombres} {$apPaterno} {$apMaterno}");
            } else {
                $d = $json['data'] ?? $json;
                $nombre = $d['razon_social'] ?? $d['nombre_o_razon_social'] ?? '';
                $domicilio = $d['direccion'] ?? $d['direccion_completa'] ?? '';
            }
        } elseif ($provider === 'apiperu') {
            $d = $json['data'] ?? $json;
            if ($tipo === 'DNI') {
                $nombre = $d['nombre_completo'] ?? trim(($d['nombres'] ?? '') . ' ' . ($d['apellido_paterno'] ?? '') . ' ' . ($d['apellido_materno'] ?? ''));
            } else {
                $nombre = $d['nombre_o_razon_social'] ?? $d['razon_social'] ?? '';
                $domicilio = $d['direccion'] ?? '';
            }
        } elseif ($provider === 'apisperu') {
            if ($tipo === 'DNI') {
                $nombre = trim(($json['nombres'] ?? '') . ' ' . ($json['apellidoPaterno'] ?? '') . ' ' . ($json['apellidoMaterno'] ?? ''));
            } else {
                $nombre = $json['razonSocial'] ?? '';
                $domicilio = $json['direccion'] ?? '';
            }
        }

        if (empty($nombre)) {
            return ['success' => false];
        }

        return [
            'success' => true,
            'data'    => [
                'tipo_documento'  => $tipo,
                'nombre_completo' => Security::cleanString($nombre, 150),
                'domicilio'       => Security::cleanString($domicilio, 255)
            ]
        ];
    }
}
