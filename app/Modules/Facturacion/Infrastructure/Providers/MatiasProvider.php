<?php

namespace App\Modules\Facturacion\Infrastructure\Providers;

use App\Modules\Facturacion\Application\Interfaces\FacturacionProviderInterface;
use App\Modules\Facturacion\Application\DTOs\SolicitudFacturaDTO;
use App\Modules\Facturacion\Application\DTOs\RespuestaFacturacionDTO;
use App\Modules\Facturacion\Application\DTOs\SolicitudDocumentoSoporteDTO;
use App\Modules\Facturacion\Application\DTOs\RespuestaDocumentoSoporteDTO;
use App\Modules\Facturacion\Application\DTOs\SolicitudNotaCreditoDTO;
use App\Modules\Facturacion\Application\DTOs\RespuestaNotaCreditoDTO;
use App\Modules\Facturacion\Application\DTOs\SolicitudNotaDebitoDTO;
use App\Modules\Facturacion\Application\DTOs\RespuestaNotaDebitoDTO;
use App\Modules\Facturacion\Infrastructure\Clients\MatiasClient;
use App\Modules\Facturacion\Infrastructure\Mappers\MatiasInvoiceMapper;
use App\Modules\Facturacion\Infrastructure\Mappers\MatiasSupportDocumentMapper;
use App\Modules\Facturacion\Infrastructure\Mappers\MatiasCreditNoteMapper;
use App\Modules\Facturacion\Infrastructure\Mappers\MatiasDebitNoteMapper;

/**
 * Proveedor de integración real con MATIAS API.
 * 
 * Mapea SolicitudFacturaDTO y SolicitudDocumentoSoporteDTO hacia los payloads de MATIAS API
 * y utiliza MatiasClient para la emisión técnica (POST /invoice y POST /ds/document).
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
        $token = $this->getToken();

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
     * Procesar la emisión del Documento Soporte Electrónico en MATIAS API.
     */
    public function emitirDocumentoSoporte(SolicitudDocumentoSoporteDTO $solicitud): RespuestaDocumentoSoporteDTO
    {
        $token = $this->getToken();

        if (empty($token)) {
            return RespuestaDocumentoSoporteDTO::error(
                mensaje: "El token de acceso de MATIAS API no está configurado en la aplicación (MATIAS_API_TOKEN).",
                estado: 'configuracion_invalida'
            );
        }

        // 1. Transformar SolicitudDocumentoSoporteDTO al payload de MATIAS API
        $payload = MatiasSupportDocumentMapper::toMatiasPayload($solicitud);

        // 2. Extraer header condicional de Sandbox
        $forceStatus = $solicitud->metadatos['force_status'] ?? null;

        // 3. Ejecutar la petición HTTP POST /ds/document mediante MatiasClient
        $result = $this->client->postSupportDocument($payload, $token, $forceStatus);

        // 4. Mapear y normalizar la respuesta recibida hacia RespuestaDocumentoSoporteDTO
        if ($result['exitoso']) {
            $data = $result['data'];

            $id = $data['uuid']
                ?? $data['cude']
                ?? $data['id']
                ?? $data['track_id']
                ?? $data['document_number']
                ?? null;

            $cude = $data['cude']
                ?? $data['uuid']
                ?? $data['document_key']
                ?? null;

            $numero = $data['number']
                ?? $data['document_number']
                ?? $solicitud->folio;

            $qr = $data['qr']
                ?? $data['qr_code']
                ?? null;

            $pdf = $data['pdf']
                ?? $data['pdf_url']
                ?? null;

            $xml = $data['xml']
                ?? $data['xml_url']
                ?? null;

            $id = is_scalar($id) ? (string) $id : null;
            $numero = is_scalar($numero) ? (string) $numero : null;
            $cude = is_scalar($cude) ? (string) $cude : null;
            $qr = is_scalar($qr) ? (string) $qr : null;
            $pdf = is_scalar($pdf) ? (string) $pdf : null;
            $xml = is_scalar($xml) ? (string) $xml : null;

            return RespuestaDocumentoSoporteDTO::exitoso(
                estado: 'emitido',
                identificadorExterno: $id,
                numeroDocumento: $numero,
                cude: $cude,
                qrCode: $qr,
                pdfUrl: $pdf,
                xmlUrl: $xml,
                mensaje: is_scalar($result['mensaje'] ?? null)
                    ? (string) $result['mensaje']
                    : 'Documento Soporte procesado exitosamente por la DIAN.',
                datosTecnicos: $data
            );
        }

        return RespuestaDocumentoSoporteDTO::error(
            mensaje: $result['mensaje'],
            errores: $result['errores'] ?? [],
            estado: 'rechazado',
            datosTecnicos: $result['data'] ?? []
        );
    }

    /**
     * Procesar la emisión de la Nota Crédito Electrónica en MATIAS API.
     */
    public function emitirNotaCredito(SolicitudNotaCreditoDTO $solicitud): RespuestaNotaCreditoDTO
    {
        $token = $this->getToken();

        if (empty($token)) {
            return RespuestaNotaCreditoDTO::error(
                mensaje: "El token de acceso de MATIAS API no está configurado en la aplicación (MATIAS_API_TOKEN).",
                estado: 'configuracion_invalida'
            );
        }

        // 1. Transformar SolicitudNotaCreditoDTO al payload de MATIAS API
        $payload = MatiasCreditNoteMapper::toMatiasPayload($solicitud);

        // 2. Extraer header condicional de Sandbox
        $forceStatus = $solicitud->metadatos['force_status'] ?? null;

        // 3. Ejecutar la petición HTTP POST /notes/credit mediante MatiasClient
        $result = $this->client->postCreditNote($payload, $token, $forceStatus);

        // 4. Mapear y normalizar la respuesta recibida hacia RespuestaNotaCreditoDTO
        if ($result['exitoso']) {
            $data = $result['data'];

            $id = $data['uuid']
                ?? $data['cude']
                ?? $data['cufe']
                ?? $data['id']
                ?? $data['track_id']
                ?? $data['document_number']
                ?? null;

            $cude = $data['cude']
                ?? $data['cufe']
                ?? $data['uuid']
                ?? $data['document_key']
                ?? null;

            $numero = $data['number']
                ?? $data['document_number']
                ?? $solicitud->folio;

            $qr = $data['qr']
                ?? $data['qr_code']
                ?? null;

            $pdf = $data['pdf']
                ?? $data['pdf_url']
                ?? null;

            $xml = $data['xml']
                ?? $data['xml_url']
                ?? null;

            $id = is_scalar($id) ? (string) $id : null;
            $numero = is_scalar($numero) ? (string) $numero : null;
            $cude = is_scalar($cude) ? (string) $cude : null;
            $qr = is_scalar($qr) ? (string) $qr : null;
            $pdf = is_scalar($pdf) ? (string) $pdf : null;
            $xml = is_scalar($xml) ? (string) $xml : null;

            return RespuestaNotaCreditoDTO::exitoso(
                estado: 'emitido',
                identificadorExterno: $id,
                numeroNotaCredito: $numero,
                cude: $cude,
                qrCode: $qr,
                pdfUrl: $pdf,
                xmlUrl: $xml,
                mensaje: is_scalar($result['mensaje'] ?? null)
                    ? (string) $result['mensaje']
                    : 'Nota Crédito procesada exitosamente por la DIAN.',
                datosTecnicos: $data
            );
        }

        return RespuestaNotaCreditoDTO::error(
            mensaje: $result['mensaje'],
            errores: $result['errores'] ?? [],
            estado: 'rechazado',
            datosTecnicos: $result['data'] ?? []
        );
    }

    /**
     * Procesar la emisión de la Nota Débito Electrónica en MATIAS API.
     */
    public function emitirNotaDebito(SolicitudNotaDebitoDTO $solicitud): RespuestaNotaDebitoDTO
    {
        $token = $this->getToken();

        if (empty($token)) {
            return RespuestaNotaDebitoDTO::error(
                mensaje: "El token de acceso de MATIAS API no está configurado en la aplicación (MATIAS_API_TOKEN).",
                estado: 'configuracion_invalida'
            );
        }

        // 1. Transformar SolicitudNotaDebitoDTO al payload de MATIAS API
        $payload = MatiasDebitNoteMapper::toMatiasPayload($solicitud);

        // 2. Extraer header condicional de Sandbox
        $forceStatus = $solicitud->metadatos['force_status'] ?? null;

        // 3. Ejecutar la petición HTTP POST /notes/debit mediante MatiasClient
        $result = $this->client->postDebitNote($payload, $token, $forceStatus);

        // 4. Mapear y normalizar la respuesta recibida hacia RespuestaNotaDebitoDTO
        if ($result['exitoso']) {
            $data = $result['data'];

            $id = $data['uuid']
                ?? $data['cude']
                ?? $data['cufe']
                ?? $data['id']
                ?? $data['track_id']
                ?? $data['document_number']
                ?? null;

            $cude = $data['cude']
                ?? $data['cufe']
                ?? $data['uuid']
                ?? $data['document_key']
                ?? null;

            $numero = $data['number']
                ?? $data['document_number']
                ?? $solicitud->folio;

            $qr = $data['qr']
                ?? $data['qr_code']
                ?? null;

            $pdf = $data['pdf']
                ?? $data['pdf_url']
                ?? null;

            $xml = $data['xml']
                ?? $data['xml_url']
                ?? null;

            $id = is_scalar($id) ? (string) $id : null;
            $numero = is_scalar($numero) ? (string) $numero : null;
            $cude = is_scalar($cude) ? (string) $cude : null;
            $qr = is_scalar($qr) ? (string) $qr : null;
            $pdf = is_scalar($pdf) ? (string) $pdf : null;
            $xml = is_scalar($xml) ? (string) $xml : null;

            return RespuestaNotaDebitoDTO::exitoso(
                estado: 'emitido',
                identificadorExterno: $id,
                numeroNotaDebito: $numero,
                cude: $cude,
                qrCode: $qr,
                pdfUrl: $pdf,
                xmlUrl: $xml,
                mensaje: is_scalar($result['mensaje'] ?? null)
                    ? (string) $result['mensaje']
                    : 'Nota Débito procesada exitosamente por la DIAN.',
                datosTecnicos: $data
            );
        }

        return RespuestaNotaDebitoDTO::error(
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

    protected function getToken(): ?string
    {
        $configFact = \App\Models\ConfiguracionFacturacion::first();
        return $configFact?->getEffectiveMatiasToken() ?? config('services.matias.token');
    }

    public function getClient(): MatiasClient
    {
        return $this->client;
    }
}
