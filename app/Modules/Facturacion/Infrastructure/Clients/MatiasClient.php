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

        $this->baseUrl = $customBaseUrl ?? match (strtolower(trim($ambiente))) {
            'produccion', 'production' => config('services.matias.production_url') 
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
     */
    protected function parseResponse(Response $response): array
    {
        $status = $response->status();
        $body = $response->json();

        if ($response->successful()) {
            return [
                'exitoso' => true,
                'status' => $status,
                'mensaje' => is_array($body) && isset($body['message']) 
                    ? $body['message'] 
                    : (is_array($body) && isset($body['mensaje']) ? $body['mensaje'] : 'Operación exitosa en MATIAS API.'),
                'data' => is_array($body) ? $body : [],
            ];
        }

        $mensaje = is_array($body) && isset($body['message']) 
            ? $body['message'] 
            : (is_array($body) && isset($body['mensaje']) ? $body['mensaje'] : "HTTP {$status}: La API de MATIAS devolvió un error.");
        
        $errors = is_array($body) && isset($body['errors']) 
            ? $body['errors'] 
            : (is_array($body) && isset($body['errores']) ? $body['errores'] : []);

        return [
            'exitoso' => false,
            'status' => $status,
            'mensaje' => $mensaje,
            'errores' => is_array($errors) ? $errors : [$errors],
            'data' => is_array($body) ? $body : [],
        ];
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
