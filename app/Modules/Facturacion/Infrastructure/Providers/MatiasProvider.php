<?php

namespace App\Modules\Facturacion\Infrastructure\Providers;

use App\Modules\Facturacion\Application\Interfaces\FacturacionProviderInterface;
use App\Modules\Facturacion\Application\DTOs\SolicitudFacturaDTO;
use App\Modules\Facturacion\Application\DTOs\RespuestaFacturacionDTO;
use App\Modules\Facturacion\Infrastructure\Clients\MatiasClient;
use App\Modules\Facturacion\Infrastructure\Mappers\MatiasInvoiceMapper;

/**
 * Proveedor de integración real con MATIAS API.
 * 
 * Mapea SolicitudFacturaDTO al JSON de MATIAS API y utiliza MatiasClient
 * para la emisión técnica de la factura mediante POST /invoice.
 */
class MatiasProvider implements FacturacionProviderInterface
{
    protected MatiasClient $client;

    public function __construct(
        protected string $ambiente = 'sandbox',
        ?MatiasClient $client = null
    ) {
        $this->client = $client ?? new MatiasClient($ambiente);
    }

    /**
     * Procesar la emisión de la factura electrónica en MATIAS API.
     */
    public function enviarFactura(SolicitudFacturaDTO $solicitud): RespuestaFacturacionDTO
    {
        $token = config('services.matias.token');

        if (empty($token)) {
            return RespuestaFacturacionDTO::error(
                mensaje: "El token de acceso de MATIAS API no está configurado en la aplicación (MATIAS_API_TOKEN).",
                estado: 'configuracion_invalida'
            );
        }

        // 1. Transformar SolicitudFacturaDTO al payload estandarizado de MATIAS API
        $payload = MatiasInvoiceMapper::toMatiasPayload($solicitud);

        // 2. Extraer header condicional de Sandbox si viene en los metadatos
        $forceStatus = $solicitud->metadatos['force_status'] ?? null;

        // 3. Ejecutar la petición HTTP POST /invoice mediante MatiasClient
        $result = $this->client->postInvoice($payload, $token, $forceStatus);

        // 4. Mapear y normalizar la respuesta recibida hacia RespuestaFacturacionDTO
        if ($result['exitoso']) {
            $data = $result['data'];

            // Identificador externo
            $id = $data['uuid']
                ?? $data['id']
                ?? $data['track_id']
                ?? $data['document_number']
                ?? null;

            // CUFE
            $cufe = $data['cufe']
                ?? $data['document_key']
                ?? null;

            // Número de factura
            $numero = $data['number']
                ?? $data['document_number']
                ?? $solicitud->folio;

            // Código QR
            $qr = $data['qr']
                ?? $data['qr_code']
                ?? null;

            // PDF
            $pdf = $data['pdf']
                ?? $data['pdf_url']
                ?? null;

            // XML
            $xml = $data['xml']
                ?? $data['xml_url']
                ?? null;

            // Evitar "Array to string conversion"
            $id = is_scalar($id) ? (string) $id : null;
            $numero = is_scalar($numero) ? (string) $numero : null;
            $cufe = is_scalar($cufe) ? (string) $cufe : null;
            $qr = is_scalar($qr) ? (string) $qr : null;
            $pdf = is_scalar($pdf) ? (string) $pdf : null;
            $xml = is_scalar($xml) ? (string) $xml : null;

            return RespuestaFacturacionDTO::exitoso(
                estado: 'emitido',
                identificadorExterno: $id,
                numeroFactura: $numero,
                cufe: $cufe,
                qrCode: $qr,
                pdfUrl: $pdf,
                xmlUrl: $xml,
                mensaje: is_scalar($result['mensaje'] ?? null)
                    ? (string) $result['mensaje']
                    : 'Solicitud procesada por la DIAN.',
                datosTecnicos: $data
            );
        }

        return RespuestaFacturacionDTO::error(
            mensaje: $result['mensaje'],
            errores: $result['errores'] ?? [],
            estado: 'rechazado',
            datosTecnicos: $result['data'] ?? []
        );
    }

    /**
     * Consultar estado de documento electrónico en MATIAS API.
     */
    public function consultarEstado(string $identificadorExterno): RespuestaFacturacionDTO
    {
        return RespuestaFacturacionDTO::error(
            mensaje: "La consulta de estado en MATIAS API requiere confirmar el endpoint de track id correspondiente.",
            estado: 'pendiente_especificacion'
        );
    }

    public function getClient(): MatiasClient
    {
        return $this->client;
    }
}
