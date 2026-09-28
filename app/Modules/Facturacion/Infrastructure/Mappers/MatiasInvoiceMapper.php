<?php

namespace App\Modules\Facturacion\Infrastructure\Mappers;

use App\Modules\Facturacion\Application\DTOs\SolicitudFacturaDTO;

/**
 * Mapper encuadradador del DTO genérico SolicitudFacturaDTO 
 * hacia el payload JSON estandarizado de MATIAS API (POST /invoice).
 */
class MatiasInvoiceMapper
{
    /**
     * Mapear una SolicitudFacturaDTO al arreglo asociativo exigido por MATIAS API.
     */
    public static function toMatiasPayload(SolicitudFacturaDTO $solicitud): array
    {
        $meta = $solicitud->metadatos;
        $cliente = $solicitud->cliente;
        $emisor = $solicitud->emisor;
        $totales = $solicitud->totales;

        $rawDocNumber = $solicitud->folio ?? $solicitud->ventaId ?? null;
        if ($rawDocNumber === null || $rawDocNumber === '' || !is_numeric($rawDocNumber)) {
            throw new \InvalidArgumentException("El campo document_number debe ser un número entero válido.");
        }

        $payload = [
            'resolution_number' => (string)($meta['resolution_number'] ?? $emisor['resolution_number'] ?? ''),
            'prefix' => (string)($solicitud->prefijo ?? $meta['prefix'] ?? ''),
            'notes' => (string)($solicitud->observacion ?? $meta['notes'] ?? ''),
            'document_number' => (int) $rawDocNumber,
            'graphic_representation' => (int)($meta['graphic_representation'] ?? 0),
            'send_email' => (int)($meta['send_email'] ?? 1),
            'operation_type_id' => (int)($meta['operation_type_id'] ?? 1),
            'type_document_id' => (int)($meta['type_document_id'] ?? 7),
        ];

        // Mapeo de Pagos
        if (isset($meta['payments']) && is_array($meta['payments'])) {
            $payload['payments'] = $meta['payments'];
        } else {
            $payableVal = $totales['payable_amount'] ?? $totales['total_pagar'] ?? '0.00';
            $payload['payments'] = [
                [
                    'payment_method_id' => (int)($meta['payment_method_id'] ?? 1),
                    'means_payment_id' => (int)($meta['means_payment_id'] ?? 10),
                    'value_paid' => (string)number_format((float)$payableVal, 2, '.', ''),
                ]
            ];
        }

        // Mapeo de Firma / Responsables del Documento
        if (isset($meta['document_signature']) && is_array($meta['document_signature'])) {
            $payload['document_signature'] = $meta['document_signature'];
        } else {
            $payload['document_signature'] = [
                'cashier' => (string)($meta['cashier'] ?? $emisor['cajero'] ?? 'Cajero ERP'),
                'seller' => (string)($meta['seller'] ?? $emisor['vendedor'] ?? 'Vendedor ERP'),
            ];
        }

        // Mapeo de Cliente / Receptores
        $payload['customer'] = [
            'country_id' => (string)($cliente['country_id'] ?? '45'),
            'city_id' => (string)($cliente['city_id'] ?? '836'),
            'identity_document_id' => (string)($cliente['identity_document_id'] ?? $cliente['tipo_documento_id'] ?? '1'),
            'type_organization_id' => (int)($cliente['type_organization_id'] ?? $cliente['tipo_persona_id'] ?? 2),
            'tax_regime_id' => (int)($cliente['tax_regime_id'] ?? $cliente['tipo_regimen_id'] ?? 2),
            'tax_level_id' => (int)($cliente['tax_level_id'] ?? 5),
            'company_name' => (string)($cliente['company_name'] ?? $cliente['nombre_razon_social'] ?? 'CLIENTE MOSTRADOR'),
            'dni' => (string)($cliente['dni'] ?? $cliente['numero_documento'] ?? '222222222222'),
            'mobile' => (string)($cliente['mobile'] ?? $cliente['telefono'] ?? '3000000000'),
            'email' => (string)($cliente['email'] ?? 'factura@cliente.com'),
            'address' => (string)($cliente['address'] ?? $cliente['direccion'] ?? 'Dirección Conocida'),
            'postal_code' => (string)($cliente['postal_code'] ?? '661002'),
        ];

        // Mapeo de Líneas de Producto
        $lines = [];
        foreach ($solicitud->items as $item) {
            $cant = (float)($item['invoiced_quantity'] ?? $item['cantidad'] ?? 1);
            $precio = (float)($item['price_amount'] ?? $item['precio_unitario'] ?? 0);
            $lineExt = (float)($item['line_extension_amount'] ?? ($cant * $precio));

            $itemTaxes = [];
            if (isset($item['tax_totals']) && is_array($item['tax_totals'])) {
                $itemTaxes = $item['tax_totals'];
            } else {
                $taxAmount = (float)($item['tax_amount'] ?? 0);
                $percent = (float)($item['percent'] ?? 0);
                if ($taxAmount > 0 || $percent > 0) {
                    $itemTaxes[] = [
                        'tax_id' => (string)($item['tax_id'] ?? '1'),
                        'tax_amount' => $taxAmount,
                        'taxable_amount' => $lineExt,
                        'percent' => $percent,
                    ];
                }
            }

            $lines[] = [
                'invoiced_quantity' => (string)number_format($cant, 2, '.', ''),
                'quantity_units_id' => (string)($item['quantity_units_id'] ?? '1093'),
                'line_extension_amount' => (string)number_format($lineExt, 2, '.', ''),
                'free_of_charge_indicator' => (bool)($item['free_of_charge_indicator'] ?? false),
                'description' => (string)($item['description'] ?? $item['nombre'] ?? 'Producto'),
                'code' => (string)($item['code'] ?? $item['codigo'] ?? 'PROD'),
                'type_item_identifications_id' => (string)($item['type_item_identifications_id'] ?? '4'),
                'reference_price_id' => (string)($item['reference_price_id'] ?? '1'),
                'price_amount' => (string)number_format($precio, 2, '.', ''),
                'base_quantity' => (string)number_format($cant, 2, '.', ''),
                'um' => (string)($item['um'] ?? 'M'),
                'tax_totals' => $itemTaxes,
            ];
        }
        $payload['lines'] = $lines;

        // Totales Monetarios Legales
        if (isset($totales['legal_monetary_totals']) && is_array($totales['legal_monetary_totals'])) {
            $payload['legal_monetary_totals'] = $totales['legal_monetary_totals'];
        } else {
            $lineExtTot = (float)($totales['line_extension_amount'] ?? $totales['subtotal'] ?? 0);
            $taxExcl = (float)($totales['tax_exclusive_amount'] ?? $lineExtTot);
            $taxIncl = (float)($totales['tax_inclusive_amount'] ?? $totales['total_pagar'] ?? $lineExtTot);
            $payable = (float)($totales['payable_amount'] ?? $taxIncl);

            $payload['legal_monetary_totals'] = [
                'line_extension_amount' => (string)number_format($lineExtTot, 2, '.', ''),
                'tax_exclusive_amount' => (string)number_format($taxExcl, 2, '.', ''),
                'tax_inclusive_amount' => (string)number_format($taxIncl, 2, '.', ''),
                'payable_amount' => $payable,
            ];
        }

        // Totales de Impuestos Generales
        if (isset($totales['tax_totals']) && is_array($totales['tax_totals'])) {
            $payload['tax_totals'] = $totales['tax_totals'];
        } else {
            $payload['tax_totals'] = $meta['tax_totals'] ?? [];
        }

        return $payload;
    }
}
