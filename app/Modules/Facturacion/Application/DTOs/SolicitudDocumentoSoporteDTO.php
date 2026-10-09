<?php

namespace App\Modules\Facturacion\Application\DTOs;

/**
 * DTO Genérico de solicitud de Documento Soporte Electrónico (DS) en el ERP.
 * Representa los datos comerciales de compras/gastos y de emisor/proveedor
 * sin acoplamiento directo a ningún proveedor externo específico.
 */
class SolicitudDocumentoSoporteDTO
{
    public function __construct(
        public readonly ?int $compraId = null,
        public readonly ?int $gastoId = null,
        public readonly ?int $proveedorId = null,
        public readonly ?string $prefijo = null,
        public readonly ?string $folio = null,
        public readonly ?string $fechaEmision = null,
        public readonly ?string $tipoDocumento = 'documento_soporte',
        public readonly array $proveedor = [],
        public readonly array $emisor = [],
        public readonly array $items = [],
        public readonly array $totales = [],
        public readonly ?string $medioPago = null,
        public readonly ?string $observacion = null,
        public readonly array $metadatos = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            compraId: $data['compra_id'] ?? null,
            gastoId: $data['gasto_id'] ?? null,
            proveedorId: $data['proveedor_id'] ?? null,
            prefijo: $data['prefijo'] ?? null,
            folio: $data['folio'] ?? null,
            fechaEmision: $data['fecha_emision'] ?? null,
            tipoDocumento: $data['tipo_documento'] ?? 'documento_soporte',
            proveedor: $data['proveedor'] ?? [],
            emisor: $data['emisor'] ?? [],
            items: $data['items'] ?? [],
            totales: $data['totales'] ?? [],
            medioPago: $data['medio_pago'] ?? null,
            observacion: $data['observacion'] ?? null,
            metadatos: $data['metadatos'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'compra_id' => $this->compraId,
            'gasto_id' => $this->gastoId,
            'proveedor_id' => $this->proveedorId,
            'prefijo' => $this->prefijo,
            'folio' => $this->folio,
            'fecha_emision' => $this->fechaEmision,
            'tipo_documento' => $this->tipoDocumento,
            'proveedor' => $this->proveedor,
            'emisor' => $this->emisor,
            'items' => $this->items,
            'totales' => $this->totales,
            'medio_pago' => $this->medioPago,
            'observacion' => $this->observacion,
            'metadatos' => $this->metadatos,
        ];
    }
}
