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
 * - Ejecución técnica de peticiones HTTP POST /invoice.
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
        $this->baseUrl = $customBaseUrl ?? match (strtolower(trim($ambiente))) {
            'produccion', 'production' => config('services.matias.production_url') 
                ?? throw new RuntimeException("La URL de producción de MATIAS API no está configurada."),
            default => config('services.matias.sandbox_url', 'https://sandbox-api.maticerts.com'),
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
