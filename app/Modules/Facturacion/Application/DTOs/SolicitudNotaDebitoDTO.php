<?php

namespace App\Modules\Facturacion\Application\DTOs;

/**
 * DTO Genérico de solicitud de Nota Débito Electrónica en el ERP.
 * Representa los datos comerciales, discrepancia, factura de referencia, cliente y totales
 * sin acoplamiento a ningún proveedor externo específico.
 */
class SolicitudNotaDebitoDTO
{
    public function __construct(
        public readonly ?int $ventaId = null,
        public readonly ?string $prefijo = null,
        public readonly ?string $folio = null,
        public readonly ?string $fechaEmision = null,
        public readonly ?string $tipoDocumento = 'nota_debito',
        public readonly array $discrepancyResponse = [],
        public readonly array $billingReference = [],
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
            tipoDocumento: $data['tipo_documento'] ?? 'nota_debito',
            discrepancyResponse: $data['discrepancy_response'] ?? [],
            billingReference: $data['billing_reference'] ?? [],
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
            'discrepancy_response' => $this->discrepancyResponse,
            'billing_reference' => $this->billingReference,
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
