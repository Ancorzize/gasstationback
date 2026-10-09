<?php

namespace App\Modules\Compras\Infrastructure\Mappers;

use App\Models\Compra;
use App\Modules\Compras\Application\DTOs\CreateCompraDTO;
use App\Modules\Compras\Application\DTOs\DetalleCompraDTO;
use App\Modules\Compras\Application\DTOs\UpdateCompraDTO;
use App\Modules\Compras\Application\DTOs\ConfirmarCompraDTO;
use App\Modules\Facturacion\Application\DTOs\SolicitudDocumentoSoporteDTO;

class CompraMapper
{
    /**
     * @return DetalleCompraDTO[]
     */
    private static function mapDetalles(array $detalles): array
    {
        return collect($detalles)->map(function ($item) {
            return new DetalleCompraDTO(
                producto_id: (int) $item['producto_id'],
                cantidad: (float) $item['cantidad'],
                costo_unitario: (float) $item['costo_unitario'],
                iva: (float) $item['iva'],
                soldicom: (float) $item['soldicom'],
                total: (float) $item['total'],
                iva_valor: (float) $item['iva_valor'],
                sobre_tasa: isset($item['sobre_tasa']) ? (float) $item['sobre_tasa'] : 0,
            );
        })->all();
    }

    public static function fromArrayToCreateDTO(array $data, int $userId): CreateCompraDTO
    {
        $dsActivo = (bool) ($data['documento_soporte_electronico'] ?? false);
        $estadoDs = $dsActivo ? ($data['estado_documento_soporte'] ?? 'pendiente') : 'no_requerido';

        return new CreateCompraDTO(
            proveedor_id: (int) $data['proveedor_id'],
            bodega_id: (int) $data['bodega_id'],
            user_id: $userId,
            numero_documento: $data['numero_documento'] ?? null,
            fecha_compra: $data['fecha_compra'],
            fecha_vencimiento: $data['fecha_vencimiento'] ?? null,
            tipo_pago: $data['tipo_pago'],
            impuesto: (float) ($data['impuesto'] ?? 0),
            soldicom: (float) ($data['soldicom'] ?? 0),
            sobre_tasa: (float) ($data['sobre_tasa'] ?? 0),
            observacion: $data['observacion'] ?? null,
            numero_comprobante: $data['numero_comprobante'] ?? null,
            detalles: self::mapDetalles($data['detalles']),
            documento_soporte_electronico: $dsActivo,
            estado_documento_soporte: $estadoDs,
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdateCompraDTO
    {
        $dsActivo = (bool) ($data['documento_soporte_electronico'] ?? false);
        $estadoDs = $dsActivo ? ($data['estado_documento_soporte'] ?? 'pendiente') : 'no_requerido';

        return new UpdateCompraDTO(
            proveedor_id: (int) $data['proveedor_id'],
            bodega_id: (int) $data['bodega_id'],
            numero_documento: $data['numero_documento'] ?? null,
            fecha_compra: $data['fecha_compra'],
            fecha_vencimiento: $data['fecha_vencimiento'] ?? null,
            tipo_pago: $data['tipo_pago'],
            impuesto: (float) ($data['impuesto'] ?? 0),
            soldicom: (float) ($data['soldicom'] ?? 0),
            sobre_tasa: (float) ($data['sobre_tasa'] ?? 0),
            observacion: $data['observacion'] ?? null,
            numero_comprobante: $data['numero_comprobante'] ?? null,
            detalles: self::mapDetalles($data['detalles']),
            documento_soporte_electronico: $dsActivo,
            estado_documento_soporte: $estadoDs,
        );
    }

    public static function fromArrayToConfirmarDTO(array $data): ConfirmarCompraDTO
    {
        return new ConfirmarCompraDTO(caja_id: isset($data['caja_id']) ? (int) $data['caja_id'] : null);
    }

    /**
     * Mapear un modelo Compra hacia el SolicitudDocumentoSoporteDTO desacoplado.
     */
    public static function toSolicitudDocumentoSoporteDTO(Compra $compra): SolicitudDocumentoSoporteDTO
    {
        $proveedor = $compra->proveedor;

        $proveedorData = [
            'id' => $proveedor?->id,
            'nombre' => $proveedor?->nombre,
            'nit' => $proveedor?->nit,
            'direccion' => $proveedor?->direccion,
            'email' => $proveedor?->email,
            'telefono' => $proveedor?->telefono,
            'tipo_persona' => $proveedor?->tipo_persona,
            'tipo_documento_id' => $proveedor?->tipo_documento_id ?? '1',
            'tipo_organization_id' => $proveedor?->tipo_organization_id ?? 2,
            'tax_regime_id' => $proveedor?->tax_regime_id ?? 2,
            'tax_level_id' => $proveedor?->tax_level_id ?? 5,
            'ciudad_id' => $proveedor?->ciudad_id ?? '836',
            'pais_id' => $proveedor?->pais_id ?? '45',
            'codigo_postal' => $proveedor?->codigo_postal ?? '661002',
        ];

        $items = [];
        if ($compra->relationLoaded('detalles')) {
            foreach ($compra->detalles as $detalle) {
                $items[] = [
                    'producto_id' => $detalle->producto_id,
                    'codigo' => $detalle->producto?->codigo ?? 'PROD',
                    'nombre' => $detalle->producto?->nombre ?? 'Producto',
                    'cantidad' => (float) $detalle->cantidad,
                    'precio_unitario' => (float) $detalle->costo_unitario,
                    'subtotal' => (float) $detalle->subtotal,
                    'iva_valor' => (float) $detalle->iva_valor,
                    'total' => (float) $detalle->total,
                ];
            }
        }

        $totales = [
            'subtotal' => (float) $compra->subtotal,
            'impuesto' => (float) $compra->impuesto,
            'soldicom' => (float) $compra->soldicom,
            'sobre_tasa' => (float) $compra->sobre_tasa,
            'total_pagar' => (float) $compra->total,
        ];

        return new SolicitudDocumentoSoporteDTO(
            compraId: $compra->id,
            proveedorId: $compra->proveedor_id,
            prefijo: null,
            folio: (string) ($compra->numero_documento ?? $compra->id),
            fechaEmision: $compra->fecha_compra?->format('Y-m-d'),
            tipoDocumento: 'documento_soporte',
            proveedor: $proveedorData,
            emisor: [],
            items: $items,
            totales: $totales,
            medioPago: $compra->tipo_pago,
            observacion: $compra->observacion,
            metadatos: []
        );
    }
}