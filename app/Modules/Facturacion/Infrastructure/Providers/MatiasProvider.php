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

        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $extracted = $this->extractDocumentFields($data, $solicitud->folio);
        $cufeReal = $extracted['cufe'] ?? $this->client->extractCufeFromPayload($data);
        $tieneCufeValido = is_string($cufeReal) && trim($cufeReal) !== '';
        $estadoDian = $result['estado_dian'] ?? ($result['exitoso'] ? ($tieneCufeValido ? 'emitido' : 'enviado') : 'rechazado');

        // 4. Mapear y normalizar la respuesta recibida hacia RespuestaFacturacionDTO.
        // Regla obligatoria: Solo se marca 'emitido' cuando existe un CUFE real y no vacío.
        if ($result['exitoso'] && $estadoDian === 'emitido' && $tieneCufeValido) {
            return RespuestaFacturacionDTO::exitoso(
                estado: 'emitido',
                identificadorExterno: $extracted['id'],
                numeroFactura: $extracted['numero'],
                cufe: trim($cufeReal),
                qrCode: $extracted['qr'],
                pdfUrl: $extracted['pdf'],
                xmlUrl: $extracted['xml'],
                mensaje: is_scalar($result['mensaje'] ?? null)
                    ? (string) $result['mensaje']
                    : 'Solicitud procesada por la DIAN.',
                datosTecnicos: array_merge($data, [
                    'http_status' => $result['status'] ?? 200,
                ])
            );
        }

        if (in_array($estadoDian, ['enviado', 'pendiente'], true) || ($result['exitoso'] && !$tieneCufeValido)) {
            $estadoEnCurso = $estadoDian === 'pendiente' ? 'pendiente' : 'enviado';

            return new RespuestaFacturacionDTO(
                exitoso: false,
                estado: $estadoEnCurso,
                identificadorExterno: $extracted['id'],
                numeroFactura: $extracted['numero'],
                cufe: $tieneCufeValido ? trim($cufeReal) : null,
                qrCode: $extracted['qr'],
                pdfUrl: $extracted['pdf'],
                xmlUrl: $extracted['xml'],
                mensaje: is_scalar($result['mensaje'] ?? null) && trim((string) $result['mensaje']) !== ''
                    ? (string) $result['mensaje']
                    : 'Solicitud recibida por MATIAS API sin CUFE definitivo; en estado ' . $estadoEnCurso . '.',
                errores: $result['errores'] ?? [],
                datosTecnicos: array_merge($data, [
                    'http_status' => $result['status'] ?? 200,
                ])
            );
        }

        return RespuestaFacturacionDTO::error(
            mensaje: $result['mensaje'] ?? 'MATIAS/DIAN rechazó la factura electrónica.',
            errores: $result['errores'] ?? [],
            estado: 'rechazado',
            datosTecnicos: array_merge($data, [
                'http_status' => $result['status'] ?? 0,
            ])
        );
    }

    /**
     * Extraer identificadores de documento (CUFE, UUID, TrackID, QR, PDF, XML)
     * soportando estructuras planas y anidadas (raíz, data, result, response, dian_response, DianResponse).
     */
    protected function extractDocumentFields(array $data, ?string $fallbackFolio = null): array
    {
        $innerData = isset($data['data']) && is_array($data['data']) ? $data['data'] : [];
        $resultNode = isset($data['result']) && is_array($data['result'])
            ? $data['result']
            : (isset($innerData['result']) && is_array($innerData['result']) ? $innerData['result'] : []);
        $respNode = isset($data['response']) && is_array($data['response'])
            ? $data['response']
            : (isset($innerData['response']) && is_array($innerData['response'])
                ? $innerData['response']
                : (isset($resultNode['response']) && is_array($resultNode['response']) ? $resultNode['response'] : []));

        $dianNode = [];
        foreach ([$data, $innerData, $resultNode, $respNode] as $parent) {
            if (isset($parent['dian_response']) && is_array($parent['dian_response'])) {
                $dianNode = $parent['dian_response'];
                break;
            }
            if (isset($parent['DianResponse']) && is_array($parent['DianResponse'])) {
                $dianNode = $parent['DianResponse'];
                break;
            }
        }

        $cufe = $this->client->extractCufeFromPayload($data);

        $id = $data['uuid']
            ?? $data['id']
            ?? $data['track_id']
            ?? $innerData['uuid']
            ?? $innerData['id']
            ?? $innerData['track_id']
            ?? $resultNode['uuid']
            ?? $resultNode['id']
            ?? $resultNode['track_id']
            ?? $respNode['uuid']
            ?? $respNode['track_id']
            ?? $respNode['XmlDocumentKey']
            ?? $dianNode['uuid']
            ?? $dianNode['track_id']
            ?? $dianNode['XmlDocumentKey']
            ?? $cufe
            ?? $data['document_number']
            ?? $innerData['document_number']
            ?? $resultNode['document_number']
            ?? null;

        $numero = $data['number']
            ?? $data['document_number']
            ?? $innerData['number']
            ?? $innerData['document_number']
            ?? $resultNode['number']
            ?? $resultNode['document_number']
            ?? $respNode['number']
            ?? $respNode['document_number']
            ?? $dianNode['number']
            ?? $dianNode['document_number']
            ?? $fallbackFolio;

        $qr = $data['qr']
            ?? $data['qr_code']
            ?? $innerData['qr']
            ?? $innerData['qr_code']
            ?? $resultNode['qr']
            ?? $resultNode['qr_code']
            ?? $respNode['qr']
            ?? $respNode['qr_code']
            ?? (isset($data['qr']['url']) ? $data['qr']['url'] : null)
            ?? (isset($innerData['qr']['url']) ? $innerData['qr']['url'] : null)
            ?? (isset($resultNode['qr']['url']) ? $resultNode['qr']['url'] : null)
            ?? null;

        $pdf = $data['pdf']
            ?? $data['pdf_url']
            ?? $innerData['pdf']
            ?? $innerData['pdf_url']
            ?? $resultNode['pdf']
            ?? $resultNode['pdf_url']
            ?? $respNode['pdf']
            ?? $respNode['pdf_url']
            ?? (isset($data['pdf']['url']) ? $data['pdf']['url'] : null)
            ?? (isset($innerData['pdf']['url']) ? $innerData['pdf']['url'] : null)
            ?? (isset($resultNode['pdf']['url']) ? $resultNode['pdf']['url'] : null)
            ?? null;

        $xml = $data['xml']
            ?? $data['xml_url']
            ?? $innerData['xml']
            ?? $innerData['xml_url']
            ?? $resultNode['xml']
            ?? $resultNode['xml_url']
            ?? $respNode['xml']
            ?? $respNode['xml_url']
            ?? null;

        return [
            'id' => is_scalar($id) && trim((string) $id) !== '' ? trim((string) $id) : null,
            'numero' => is_scalar($numero) && trim((string) $numero) !== '' ? trim((string) $numero) : null,
            'cufe' => is_scalar($cufe) && trim((string) $cufe) !== '' ? trim((string) $cufe) : null,
            'qr' => is_scalar($qr) && trim((string) $qr) !== '' ? trim((string) $qr) : null,
            'pdf' => is_scalar($pdf) && trim((string) $pdf) !== '' ? trim((string) $pdf) : null,
            'xml' => is_scalar($xml) && trim((string) $xml) !== '' ? trim((string) $xml) : null,
        ];
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
