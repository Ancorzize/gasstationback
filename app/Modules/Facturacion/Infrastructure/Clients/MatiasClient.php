<?php

namespace App\Modules\Facturacion\Infrastructure\Clients;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\ConnectionException;
use RuntimeException;

/**
 * Cliente HTTP técnico para la comunicación con MATIAS API.
 * 
 * Responsabilidades:
 * - Base URL para Sandbox (https://sandbox-api.maticerts.com) y Producción.
 * - Encabezados HTTP (Content-Type, Accept, X-Sandbox-Force-Status).
 * - Autenticación mediante Bearer Token (Authorization: Bearer <TOKEN>).
 * - Ejecución técnica de peticiones HTTP POST /invoice y POST /ds/document.
 * - Interpretación y normalización técnica de respuestas y errores HTTP.
 */
class MatiasClient
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct(
        string $ambiente = 'sandbox',
        ?string $customBaseUrl = null,
        int $timeout = 30
    ) {
        $this->timeout = $timeout;
        $configFact = \App\Models\ConfiguracionFacturacion::first();
        $sandboxUrl = $configFact?->getEffectiveMatiasSandboxUrl() ?? config('services.matias.sandbox_url', 'https://sandbox-api.maticerts.com');
        $productionUrl = $configFact?->getEffectiveMatiasProductionUrl() ?? config('services.matias.production_url');

        $this->baseUrl = $customBaseUrl ?? match (strtolower(trim($ambiente))) {
            'produccion', 'production' => $productionUrl 
                ?? throw new RuntimeException("La URL de producción de MATIAS API no está configurada."),
            default => $sandboxUrl,
        };

        $this->baseUrl = rtrim($this->baseUrl, '/');
    }

    /**
     * Enviar solicitud de factura electrónica mediante POST /invoice.
     *
     * @param array $payload Payload JSON con la estructura exigida por MATIAS API.
     * @param string $token Token de acceso Bearer.
     * @param string|null $forceStatus Header opcional X-Sandbox-Force-Status solo para Sandbox.
     * @return array Respuesta parseada con éxito, status y datos técnicos.
     */
    public function postInvoice(array $payload, string $token, ?string $forceStatus = null): array
    {
        $url = "{$this->baseUrl}/invoice";

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (!empty($forceStatus)) {
            $headers['X-Sandbox-Force-Status'] = $forceStatus;
        }

        try {
            $response = Http::withHeaders($headers)
                ->withToken($token)
                ->timeout($this->timeout)
                ->post($url, $payload);

            return $this->parseResponse($response);
        } catch (ConnectionException $e) {
            return [
                'exitoso' => false,
                'status' => 0,
                'mensaje' => 'Error de conexión o tiempo de espera agotado con MATIAS API: ' . $e->getMessage(),
                'errores' => [$e->getMessage()],
                'data' => [],
            ];
        } catch (\Throwable $e) {
            return [
                'exitoso' => false,
                'status' => 500,
                'mensaje' => 'Excepción en cliente HTTP MATIAS: ' . $e->getMessage(),
                'errores' => [$e->getMessage()],
                'data' => [],
            ];
        }
    }

    /**
     * Enviar solicitud de Documento Soporte Electrónico mediante POST /ds/document.
     *
     * @param array $payload Payload JSON con la estructura exigida por MATIAS API para DS.
     * @param string $token Token de acceso Bearer.
     * @param string|null $forceStatus Header opcional X-Sandbox-Force-Status solo para Sandbox.
     * @return array Respuesta parseada con éxito, status y datos técnicos.
     */
    public function postSupportDocument(array $payload, string $token, ?string $forceStatus = null): array
    {
        $url = "{$this->baseUrl}/ds/document";

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (!empty($forceStatus)) {
            $headers['X-Sandbox-Force-Status'] = $forceStatus;
        }

        try {
            $response = Http::withHeaders($headers)
                ->withToken($token)
                ->timeout($this->timeout)
                ->post($url, $payload);

            return $this->parseResponse($response);
        } catch (ConnectionException $e) {
            return [
                'exitoso' => false,
                'status' => 0,
                'mensaje' => 'Error de conexión o tiempo de espera agotado con MATIAS API (DS): ' . $e->getMessage(),
                'errores' => [$e->getMessage()],
                'data' => [],
            ];
        } catch (\Throwable $e) {
            return [
                'exitoso' => false,
                'status' => 500,
                'mensaje' => 'Excepción en cliente HTTP MATIAS (DS): ' . $e->getMessage(),
                'errores' => [$e->getMessage()],
                'data' => [],
            ];
        }
    }

    /**
     * Enviar solicitud de Nota Crédito Electrónica mediante POST /notes/credit.
     *
     * @param array $payload Payload JSON con la estructura exigida por MATIAS API para Nota Crédito.
     * @param string $token Token de acceso Bearer.
     * @param string|null $forceStatus Header opcional X-Sandbox-Force-Status solo para Sandbox.
     * @return array Respuesta parseada con éxito, status y datos técnicos.
     */
    public function postCreditNote(array $payload, string $token, ?string $forceStatus = null): array
    {
        $url = "{$this->baseUrl}/notes/credit";

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (!empty($forceStatus)) {
            $headers['X-Sandbox-Force-Status'] = $forceStatus;
        }

        try {
            $response = Http::withHeaders($headers)
                ->withToken($token)
                ->timeout($this->timeout)
                ->post($url, $payload);

            return $this->parseResponse($response);
        } catch (ConnectionException $e) {
            return [
                'exitoso' => false,
                'status' => 0,
                'mensaje' => 'Error de conexión o tiempo de espera agotado con MATIAS API (NC): ' . $e->getMessage(),
                'errores' => [$e->getMessage()],
                'data' => [],
            ];
        } catch (\Throwable $e) {
            return [
                'exitoso' => false,
                'status' => 500,
                'mensaje' => 'Excepción en cliente HTTP MATIAS (NC): ' . $e->getMessage(),
                'errores' => [$e->getMessage()],
                'data' => [],
            ];
        }
    }

    /**
     * Enviar solicitud de Nota Débito Electrónica mediante POST /notes/debit.
     *
     * @param array $payload Payload JSON con la estructura exigida por MATIAS API para Nota Débito.
     * @param string $token Token de acceso Bearer.
     * @param string|null $forceStatus Header opcional X-Sandbox-Force-Status solo para Sandbox.
     * @return array Respuesta parseada con éxito, status y datos técnicos.
     */
    public function postDebitNote(array $payload, string $token, ?string $forceStatus = null): array
    {
        $url = "{$this->baseUrl}/notes/debit";

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (!empty($forceStatus)) {
            $headers['X-Sandbox-Force-Status'] = $forceStatus;
        }

        try {
            $response = Http::withHeaders($headers)
                ->withToken($token)
                ->timeout($this->timeout)
                ->post($url, $payload);

            return $this->parseResponse($response);
        } catch (ConnectionException $e) {
            return [
                'exitoso' => false,
                'status' => 0,
                'mensaje' => 'Error de conexión o tiempo de espera agotado con MATIAS API (ND): ' . $e->getMessage(),
                'errores' => [$e->getMessage()],
                'data' => [],
            ];
        } catch (\Throwable $e) {
            return [
                'exitoso' => false,
                'status' => 500,
                'mensaje' => 'Excepción en cliente HTTP MATIAS (ND): ' . $e->getMessage(),
                'errores' => [$e->getMessage()],
                'data' => [],
            ];
        }
    }

    /**
     * Enviar solicitud de Nómina Electrónica mediante POST /ep/payroll.
     *
     * @param array $payload Payload JSON con la estructura exigida por MATIAS API para Nómina Electrónica.
     * @param string $token Token de acceso Bearer.
     * @param string|null $forceStatus Header opcional X-Sandbox-Force-Status solo para Sandbox.
     * @return array Respuesta parseada con éxito, status y datos técnicos.
     */
    public function postPayroll(array $payload, string $token, ?string $forceStatus = null): array
    {
        $url = "{$this->baseUrl}/ep/payroll";

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (!empty($forceStatus)) {
            $headers['X-Sandbox-Force-Status'] = $forceStatus;
        }

        try {
            $response = Http::withHeaders($headers)
                ->withToken($token)
                ->timeout($this->timeout)
                ->post($url, $payload);

            return $this->parseResponse($response);
        } catch (ConnectionException $e) {
            return [
                'exitoso' => false,
                'status' => 0,
                'mensaje' => 'Error de conexión o tiempo de espera agotado con MATIAS API (Nómina Electrónica): ' . $e->getMessage(),
                'errores' => [$e->getMessage()],
                'data' => [],
            ];
        } catch (\Throwable $e) {
            return [
                'exitoso' => false,
                'status' => 500,
                'mensaje' => 'Excepción en cliente HTTP MATIAS (Nómina Electrónica): ' . $e->getMessage(),
                'errores' => [$e->getMessage()],
                'data' => [],
            ];
        }
    }

    /**
     * Enviar solicitud de Reemplazo de Nómina Electrónica mediante POST /ep/payroll/replace.
     *
     * @param array $payload Payload JSON con la estructura exigida por MATIAS API para Reemplazo de Nómina.
     * @param string $token Token de acceso Bearer.
     * @param string|null $forceStatus Header opcional X-Sandbox-Force-Status solo para Sandbox.
     * @return array Respuesta parseada con éxito, status y datos técnicos.
     */
    public function replacePayroll(array $payload, string $token, ?string $forceStatus = null): array
    {
        $url = "{$this->baseUrl}/ep/payroll/replace";

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (!empty($forceStatus)) {
            $headers['X-Sandbox-Force-Status'] = $forceStatus;
        }

        try {
            $response = Http::withHeaders($headers)
                ->withToken($token)
                ->timeout($this->timeout)
                ->post($url, $payload);

            return $this->parseResponse($response);
        } catch (ConnectionException $e) {
            return [
                'exitoso' => false,
                'status' => 0,
                'mensaje' => 'Error de conexión o tiempo de espera agotado con MATIAS API (Reemplazo Nómina): ' . $e->getMessage(),
                'errores' => [$e->getMessage()],
                'data' => [],
            ];
        } catch (\Throwable $e) {
            return [
                'exitoso' => false,
                'status' => 500,
                'mensaje' => 'Excepción en cliente HTTP MATIAS (Reemplazo Nómina): ' . $e->getMessage(),
                'errores' => [$e->getMessage()],
                'data' => [],
            ];
        }
    }

    /**
     * Enviar solicitud de Eliminación de Nómina Electrónica mediante POST /ep/payroll/delete.
     *
     * @param array $payload Payload JSON con la estructura exigida por MATIAS API para Eliminación de Nómina.
     * @param string $token Token de acceso Bearer.
     * @param string|null $forceStatus Header opcional X-Sandbox-Force-Status solo para Sandbox.
     * @return array Respuesta parseada con éxito, status y datos técnicos.
     */
    public function deletePayroll(array $payload, string $token, ?string $forceStatus = null): array
    {
        $url = "{$this->baseUrl}/ep/payroll/delete";

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (!empty($forceStatus)) {
            $headers['X-Sandbox-Force-Status'] = $forceStatus;
        }

        try {
            $response = Http::withHeaders($headers)
                ->withToken($token)
                ->timeout($this->timeout)
                ->post($url, $payload);

            return $this->parseResponse($response);
        } catch (ConnectionException $e) {
            return [
                'exitoso' => false,
                'status' => 0,
                'mensaje' => 'Error de conexión o tiempo de espera agotado con MATIAS API (Eliminación Nómina): ' . $e->getMessage(),
                'errores' => [$e->getMessage()],
                'data' => [],
            ];
        } catch (\Throwable $e) {
            return [
                'exitoso' => false,
                'status' => 500,
                'mensaje' => 'Excepción en cliente HTTP MATIAS (Eliminación Nómina): ' . $e->getMessage(),
                'errores' => [$e->getMessage()],
                'data' => [],
            ];
        }
    }

    /**
     * Interpretar y normalizar la respuesta HTTP devuelta por la API de MATIAS.
     * Evalúa tanto el código HTTP como la estructura de validación interna de MATIAS/DIAN
     * (raíz, data, response, IsValid, StatusCode, CUFE/XmlDocumentKey).
     */
    protected function parseResponse(Response $response): array
    {
        $status = $response->status();
        $body = $response->json();
        $bodyArray = is_array($body) ? $body : [];

        $mensaje = $this->extractMessageFromPayload($bodyArray, $status, $response->successful());
        $errores = $this->extractErrorsFromPayload($bodyArray);

        if (!$response->successful()) {
            return [
                'exitoso' => false,
                'estado_dian' => 'rechazado',
                'status' => $status,
                'mensaje' => $mensaje,
                'errores' => $errores,
                'data' => $bodyArray,
            ];
        }

        // Inspección semántica de respuestas HTTP 2xx:
        // No se considera emitido únicamente porque el status HTTP sea 200/201.
        $evaluation = $this->evaluateSemanticStatus($bodyArray);

        if ($evaluation['rechazado']) {
            return [
                'exitoso' => false,
                'estado_dian' => 'rechazado',
                'status' => $status,
                'mensaje' => $evaluation['mensaje'] ?? $mensaje,
                'errores' => !empty($evaluation['errores']) ? $evaluation['errores'] : $errores,
                'data' => $bodyArray,
            ];
        }

        return [
            'exitoso' => $evaluation['emitido'],
            'estado_dian' => $evaluation['estado'],
            'status' => $status,
            'mensaje' => $evaluation['mensaje'] ?? $mensaje,
            'errores' => !empty($evaluation['errores']) ? $evaluation['errores'] : $errores,
            'data' => $bodyArray,
        ];
    }

    /**
     * Extraer los nodos anidados posibles de una respuesta MATIAS/DIAN
     * (data, result, response, dian_response, DianResponse).
     */
    protected function resolveNestedNodes(array $body): array
    {
        $dataNode = isset($body['data']) && is_array($body['data']) ? $body['data'] : [];
        $resultNode = isset($body['result']) && is_array($body['result'])
            ? $body['result']
            : (isset($dataNode['result']) && is_array($dataNode['result']) ? $dataNode['result'] : []);
        $respNode = isset($body['response']) && is_array($body['response'])
            ? $body['response']
            : (isset($dataNode['response']) && is_array($dataNode['response'])
                ? $dataNode['response']
                : (isset($resultNode['response']) && is_array($resultNode['response']) ? $resultNode['response'] : []));

        $dianNode = [];
        foreach ([$body, $dataNode, $resultNode, $respNode] as $candidateParent) {
            if (isset($candidateParent['dian_response']) && is_array($candidateParent['dian_response'])) {
                $dianNode = $candidateParent['dian_response'];
                break;
            }
            if (isset($candidateParent['DianResponse']) && is_array($candidateParent['DianResponse'])) {
                $dianNode = $candidateParent['DianResponse'];
                break;
            }
        }

        return [
            'data' => $dataNode,
            'result' => $resultNode,
            'response' => $respNode,
            'dian' => $dianNode,
        ];
    }

    /**
     * Evaluar indicadores DIAN/MATIAS en la respuesta (IsValid, StatusCode, success, CUFE/XmlDocumentKey).
     * Nunca marca un documento como 'emitido' si no existe un CUFE/CUDE real y no vacío.
     */
    protected function evaluateSemanticStatus(array $body): array
    {
        $nodes = $this->resolveNestedNodes($body);
        $dataNode = $nodes['data'];
        $resultNode = $nodes['result'];
        $respNode = $nodes['response'];
        $dianNode = $nodes['dian'];

        // 1. Verificar bandera explícita success / status booleana en raíz, data o result
        foreach ([$body, $dataNode, $resultNode] as $node) {
            if (array_key_exists('success', $node) && $node['success'] === false) {
                return [
                    'emitido' => false,
                    'rechazado' => true,
                    'estado' => 'rechazado',
                    'mensaje' => $this->extractMessageFromPayload($body, 200, false),
                    'errores' => $this->extractErrorsFromPayload($body),
                ];
            }
            if (isset($node['status']) && (is_string($node['status']) && in_array(strtolower(trim($node['status'])), ['error', 'failed', 'rejected', 'rechazado'], true))) {
                return [
                    'emitido' => false,
                    'rechazado' => true,
                    'estado' => 'rechazado',
                    'mensaje' => $this->extractMessageFromPayload($body, 200, false),
                    'errores' => $this->extractErrorsFromPayload($body),
                ];
            }
        }

        // 2. Verificar IsValid / is_valid en response, dian_response, DianResponse, result, data o raíz
        $isValidRaw = $respNode['IsValid']
            ?? $respNode['is_valid']
            ?? $dianNode['IsValid']
            ?? $dianNode['is_valid']
            ?? $resultNode['IsValid']
            ?? $resultNode['is_valid']
            ?? $dataNode['IsValid']
            ?? $dataNode['is_valid']
            ?? $body['IsValid']
            ?? $body['is_valid']
            ?? null;

        $isValidBool = null;
        if ($isValidRaw !== null) {
            $isValidBool = filter_var($isValidRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isValidBool === false || (is_string($isValidRaw) && strtolower(trim($isValidRaw)) === 'false')) {
                $statusDesc = $respNode['StatusDescription']
                    ?? $respNode['StatusMessage']
                    ?? $dianNode['StatusDescription']
                    ?? $dianNode['StatusMessage']
                    ?? $resultNode['StatusDescription']
                    ?? $resultNode['StatusMessage']
                    ?? null;
                return [
                    'emitido' => false,
                    'rechazado' => true,
                    'estado' => 'rechazado',
                    'mensaje' => is_scalar($statusDesc) && trim((string) $statusDesc) !== ''
                        ? (string) $statusDesc
                        : $this->extractMessageFromPayload($body, 200, false),
                    'errores' => $this->extractErrorsFromPayload($body),
                ];
            }
        }

        // 3. Verificar StatusCode DIAN (00 = Procesado Correctamente; 99 u otros con errores = Rechazado; 98 = En proceso)
        $statusCode = $respNode['StatusCode']
            ?? $respNode['status_code']
            ?? $dianNode['StatusCode']
            ?? $dianNode['status_code']
            ?? $resultNode['StatusCode']
            ?? $resultNode['status_code']
            ?? $dataNode['StatusCode']
            ?? $dataNode['status_code']
            ?? $body['StatusCode']
            ?? $body['status_code']
            ?? null;

        if ($statusCode !== null && is_scalar($statusCode)) {
            $codeStr = trim((string) $statusCode);
            if ($codeStr === '98') {
                return [
                    'emitido' => false,
                    'rechazado' => false,
                    'estado' => 'enviado',
                    'mensaje' => 'Documento recibido por MATIAS/DIAN y en procesamiento (StatusCode 98).',
                    'errores' => [],
                ];
            }
            if ($codeStr !== '' && $codeStr !== '00' && $codeStr !== '0' && $codeStr !== '200' && $codeStr !== '201') {
                $statusDesc = $respNode['StatusDescription']
                    ?? $respNode['StatusMessage']
                    ?? $dianNode['StatusDescription']
                    ?? $resultNode['StatusDescription']
                    ?? null;
                return [
                    'emitido' => false,
                    'rechazado' => true,
                    'estado' => 'rechazado',
                    'mensaje' => is_scalar($statusDesc) && trim((string) $statusDesc) !== ''
                        ? "DIAN StatusCode {$codeStr}: {$statusDesc}"
                        : "Documento rechazado con StatusCode {$codeStr}.",
                    'errores' => $this->extractErrorsFromPayload($body),
                ];
            }
        }

        // 4. Verificar si existen errores explícitos de rechazo en ErrorMessage / errors
        $erroresDetectados = $this->extractErrorsFromPayload($body);
        $cufe = $this->extractCufeFromPayload($body);

        if (!empty($erroresDetectados) && $isValidBool !== true) {
            return [
                'emitido' => false,
                'rechazado' => true,
                'estado' => 'rechazado',
                'mensaje' => $this->extractMessageFromPayload($body, 200, false),
                'errores' => $erroresDetectados,
            ];
        }

        // 5. Regla obligatoria: No marcar como 'emitido' si no hay CUFE/CUDE real y no vacío
        if ($cufe === null || trim($cufe) === '') {
            return [
                'emitido' => false,
                'rechazado' => false,
                'estado' => 'enviado',
                'mensaje' => $this->extractMessageFromPayload(
                    $body,
                    200,
                    true,
                    'Documento enviado a MATIAS API, pendiente de confirmación de CUFE por la DIAN.'
                ),
                'errores' => [],
            ];
        }

        return [
            'emitido' => true,
            'rechazado' => false,
            'estado' => 'emitido',
            'cufe' => $cufe,
            'mensaje' => $this->extractMessageFromPayload($body, 200, true),
            'errores' => [],
        ];
    }

    /**
     * Extraer CUFE/CUDE/CUNE/XmlDocumentKey desde cualquier nivel de anidamiento de MATIAS
     * (raíz, data, result, response, dian_response, DianResponse).
     */
    public function extractCufeFromPayload(array $body): ?string
    {
        $nodes = $this->resolveNestedNodes($body);
        $dataNode = $nodes['data'];
        $resultNode = $nodes['result'];
        $respNode = $nodes['response'];
        $dianNode = $nodes['dian'];

        $candidates = [];
        foreach ([$body, $dataNode, $resultNode, $respNode, $dianNode] as $node) {
            if (!is_array($node) || empty($node)) {
                continue;
            }
            $candidates[] = $node['cufe'] ?? null;
            $candidates[] = $node['CUFE'] ?? null;
            $candidates[] = $node['cude'] ?? null;
            $candidates[] = $node['CUDE'] ?? null;
            $candidates[] = $node['cune'] ?? null;
            $candidates[] = $node['XmlDocumentKey'] ?? null;
            $candidates[] = $node['xml_document_key'] ?? null;
            $candidates[] = $node['document_key'] ?? null;
        }

        foreach ($candidates as $candidate) {
            if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                return trim((string) $candidate);
            }
        }

        return null;
    }

    protected function extractMessageFromPayload(
        array $body,
        int $status,
        bool $successful,
        ?string $defaultSuccessMessage = null
    ): string {
        $nodes = $this->resolveNestedNodes($body);
        $dataNode = $nodes['data'];
        $resultNode = $nodes['result'];
        $respNode = $nodes['response'];
        $dianNode = $nodes['dian'];

        $candidates = [
            $body['message'] ?? null,
            $body['mensaje'] ?? null,
            $respNode['StatusDescription'] ?? null,
            $respNode['StatusMessage'] ?? null,
            $respNode['message'] ?? null,
            $dianNode['StatusDescription'] ?? null,
            $dianNode['StatusMessage'] ?? null,
            $dianNode['message'] ?? null,
            $resultNode['StatusDescription'] ?? null,
            $resultNode['StatusMessage'] ?? null,
            $resultNode['message'] ?? null,
            $resultNode['mensaje'] ?? null,
            $dataNode['message'] ?? null,
            $dataNode['mensaje'] ?? null,
        ];

        foreach ($candidates as $msg) {
            if (is_scalar($msg) && trim((string) $msg) !== '') {
                return trim((string) $msg);
            }
        }

        return $successful
            ? ($defaultSuccessMessage ?? 'Operación exitosa en MATIAS API.')
            : "HTTP {$status}: La API de MATIAS devolvió un error.";
    }

    protected function extractErrorsFromPayload(array $body): array
    {
        $nodes = $this->resolveNestedNodes($body);
        $dataNode = $nodes['data'];
        $resultNode = $nodes['result'];
        $respNode = $nodes['response'];
        $dianNode = $nodes['dian'];

        $rawErrors = $body['errors']
            ?? $body['errores']
            ?? $respNode['ErrorMessage']
            ?? $respNode['errors']
            ?? $dianNode['ErrorMessage']
            ?? $dianNode['errors']
            ?? $resultNode['ErrorMessage']
            ?? $resultNode['errors']
            ?? $dataNode['errors']
            ?? $dataNode['ErrorMessage']
            ?? [];

        if (empty($rawErrors)) {
            return [];
        }

        if (is_array($rawErrors)) {
            if (isset($rawErrors['string'])) {
                return is_array($rawErrors['string']) ? $rawErrors['string'] : [$rawErrors['string']];
            }
            return $rawErrors;
        }

        return [(string) $rawErrors];
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
