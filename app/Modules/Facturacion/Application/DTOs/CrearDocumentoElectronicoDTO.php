<?php

namespace App\Modules\Facturacion\Application\DTOs;

class CrearDocumentoElectronicoDTO
{
    public function __construct(
        public readonly ?int $ventaId = null,
        public readonly ?int $configuracionFacturacionId = null,
        public readonly string $tipoDocumento = 'factura_electronica',
        public readonly string $estado = 'pendiente',
        public readonly string $proveedor = 'matias',
        public readonly string $ambiente = 'sandbox',
        public readonly ?string $identificadorExterno = null,
        public readonly ?string $cufe = null,
        public readonly ?string $numeroDocumento = null,
        public readonly ?string $prefijo = null,
        public readonly ?string $trackId = null,
        public readonly ?string $mensaje = null,
        public readonly ?array $errores = null,
        public readonly ?array $datosTecnicos = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            ventaId: $data['venta_id'] ?? null,
            configuracionFacturacionId: $data['configuracion_facturacion_id'] ?? null,
            tipoDocumento: $data['tipo_documento'] ?? 'factura_electronica',
            estado: $data['estado'] ?? 'pendiente',
            proveedor: $data['proveedor'] ?? 'matias',
            ambiente: $data['ambiente'] ?? 'sandbox',
            identificadorExterno: $data['identificador_externo'] ?? null,
            cufe: $data['cufe'] ?? null,
            numeroDocumento: $data['numero_documento'] ?? null,
            prefijo: $data['prefijo'] ?? null,
            trackId: $data['track_id'] ?? null,
            mensaje: $data['mensaje'] ?? null,
            errores: $data['errores'] ?? null,
            datosTecnicos: $data['datos_tecnicos'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'venta_id' => $this->ventaId,
            'configuracion_facturacion_id' => $this->configuracionFacturacionId,
            'tipo_documento' => $this->tipoDocumento,
            'estado' => $this->estado,
            'proveedor' => $this->proveedor,
            'ambiente' => $this->ambiente,
            'identificador_externo' => $this->identificadorExterno,
            'cufe' => $this->cufe,
            'numero_documento' => $this->numeroDocumento,
            'prefijo' => $this->prefijo,
            'track_id' => $this->trackId,
            'mensaje' => $this->mensaje,
            'errores' => $this->errores,
            'datos_tecnicos' => $this->datosTecnicos,
        ];
    }
}
