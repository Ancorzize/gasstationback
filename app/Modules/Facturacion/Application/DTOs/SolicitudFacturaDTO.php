<?php

namespace App\Modules\Facturacion\Application\DTOs;

/**
 * DTO Genérico de solicitud de factura electrónica en el ERP.
 * Representa los datos comerciales y de emisor/receptor necesarios
 * sin acoplamiento a ningún proveedor externo específico.
 */
class SolicitudFacturaDTO
{
    public function __construct(
        public readonly ?int $ventaId = null,
        public readonly ?string $prefijo = null,
        public readonly ?string $folio = null,
        public readonly ?string $fechaEmision = null,
        public readonly ?string $tipoDocumento = 'factura_venta',
        public readonly array $cliente = [],
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
            ventaId: $data['venta_id'] ?? null,
            prefijo: $data['prefijo'] ?? null,
            folio: $data['folio'] ?? null,
            fechaEmision: $data['fecha_emision'] ?? null,
            tipoDocumento: $data['tipo_documento'] ?? 'factura_venta',
            cliente: $data['cliente'] ?? [],
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
            'venta_id' => $this->ventaId,
            'prefijo' => $this->prefijo,
            'folio' => $this->folio,
            'fecha_emision' => $this->fechaEmision,
            'tipo_documento' => $this->tipoDocumento,
            'cliente' => $this->cliente,
            'emisor' => $this->emisor,
            'items' => $this->items,
            'totales' => $this->totales,
            'medio_pago' => $this->medioPago,
            'observacion' => $this->observacion,
            'metadatos' => $this->metadatos,
        ];
    }
}
