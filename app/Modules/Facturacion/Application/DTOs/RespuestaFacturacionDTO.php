<?php

namespace App\Modules\Facturacion\Application\DTOs;

/**
 * DTO Genérico de respuesta de facturación electrónica.
 * Normaliza los resultados entregados por cualquier proveedor de facturación.
 */
class RespuestaFacturacionDTO
{
    public function __construct(
        public readonly bool $exitoso,
        public readonly string $estado,
        public readonly ?string $identificadorExterno = null,
        public readonly ?string $numeroFactura = null,
        public readonly ?string $cufe = null,
        public readonly ?string $qrCode = null,
        public readonly ?string $pdfUrl = null,
        public readonly ?string $xmlUrl = null,
        public readonly ?string $mensaje = null,
        public readonly array $errores = [],
        public readonly array $datosTecnicos = []
    ) {}

    public static function exitoso(
        string $estado = 'emitido',
        ?string $identificadorExterno = null,
        ?string $numeroFactura = null,
        ?string $cufe = null,
        ?string $qrCode = null,
        ?string $pdfUrl = null,
        ?string $xmlUrl = null,
        ?string $mensaje = 'Factura generada exitosamente.',
        array $datosTecnicos = []
    ): self {
        return new self(
            exitoso: true,
            estado: $estado,
            identificadorExterno: $identificadorExterno,
            numeroFactura: $numeroFactura,
            cufe: $cufe,
            qrCode: $qrCode,
            pdfUrl: $pdfUrl,
            xmlUrl: $xmlUrl,
            mensaje: $mensaje,
            errores: [],
            datosTecnicos: $datosTecnicos
        );
    }

    public static function error(
        string $mensaje,
        array $errores = [],
        string $estado = 'error',
        array $datosTecnicos = []
    ): self {
        return new self(
            exitoso: false,
            estado: $estado,
            identificadorExterno: null,
            numeroFactura: null,
            cufe: null,
            qrCode: null,
            pdfUrl: null,
            xmlUrl: null,
            mensaje: $mensaje,
            errores: $errores,
            datosTecnicos: $datosTecnicos
        );
    }

    public function toArray(): array
    {
        return [
            'exitoso' => $this->exitoso,
            'estado' => $this->estado,
            'identificador_externo' => $this->identificadorExterno,
            'numero_factura' => $this->numeroFactura,
            'cufe' => $this->cufe,
            'qr_code' => $this->qrCode,
            'pdf_url' => $this->pdfUrl,
            'xml_url' => $this->xmlUrl,
            'mensaje' => $this->mensaje,
            'errores' => $this->errores,
            'datos_tecnicos' => $this->datosTecnicos,
        ];
    }
}
