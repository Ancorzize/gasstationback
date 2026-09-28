<?php

namespace App\Modules\Facturacion\Application\DTOs;

class ActualizarDocumentoElectronicoDTO
{
    public function __construct(
        public readonly ?string $estado = null,
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
            estado: $data['estado'] ?? null,
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
        return array_filter([
            'estado' => $this->estado,
            'identificador_externo' => $this->identificadorExterno,
            'cufe' => $this->cufe,
            'numero_documento' => $this->numeroDocumento,
            'prefijo' => $this->prefijo,
            'track_id' => $this->trackId,
            'mensaje' => $this->mensaje,
            'errores' => $this->errores,
            'datos_tecnicos' => $this->datosTecnicos,
        ], fn ($value) => $value !== null);
    }
}
