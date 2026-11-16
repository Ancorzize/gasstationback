<?php

namespace App\Modules\Facturacion\Infrastructure\Mappers;

use App\Modules\Facturacion\Application\DTOs\SolicitudFacturaDTO;
use App\Modules\Facturacion\Infrastructure\Validators\MatiasInvoicePayloadValidator;

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
        $payload['customer'] = MatiasCustomerMapper::toMatiasCustomer([
            'identity_document_id' => (string)($cliente['identity_document_id'] ?? $cliente['tipo_documento_id'] ?? '1'),
            'type_organization_id' => (int)($cliente['type_organization_id'] ?? $cliente['tipo_persona_id'] ?? 2),
            'tax_regime_id' => (int)($cliente['tax_regime_id'] ?? $cliente['tipo_regimen_id'] ?? 2),
            'tax_level_id' => (int)($cliente['tax_level_id'] ?? 5),
            'company_name' => (string)($cliente['company_name'] ?? $cliente['nombre_razon_social'] ?? 'CLIENTE MOSTRADOR'),
            'dni' => (string)($cliente['dni'] ?? $cliente['numero_documento'] ?? '222222222222'),
            'mobile' => (string)($cliente['mobile'] ?? $cliente['telefono'] ?? '3000000000'),
            'email' => (string)($cliente['email'] ?? 'factura@cliente.com'),
            'address' => (string)($cliente['address'] ?? $cliente['direccion'] ?? ''),
            'postal_code' => (string)($cliente['postal_code'] ?? ''),
            'country_iso' => $cliente['country_iso'] ?? null,
            'city_dane_code' => $cliente['city_dane_code'] ?? null,
            'city_local_id' => $cliente['city_local_id'] ?? null,
        ]);

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
                    $taxId = trim((string)($item['tax_id'] ?? ''));
                    if ($taxId === '') {
                        throw new \InvalidArgumentException(
                            'No se puede construir tax_totals: falta el tax_id confirmado para una línea con impuesto.'
                        );
                    }
                    $itemTaxes[] = [
                        'tax_id' => $taxId,
                        'tax_amount' => $taxAmount,
                        'taxable_amount' => $lineExt,
                        'percent' => $percent,
                    ];
                }
            }
            $itemTaxes = self::removeDuplicateLineTaxes($itemTaxes);

            $allowanceCharges = $item['allowance_charges'] ?? [];
            if (!is_array($allowanceCharges)) {
                throw new \InvalidArgumentException('lines allowance_charges debe ser un arreglo.');
            }

            $mappedLine = [
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
            if ($allowanceCharges !== []) {
                $mappedLine['allowance_charges'] = $allowanceCharges;
            }
            $lines[] = $mappedLine;
        }
        $payload['lines'] = $lines;

        $lineExtensionTotal = round(array_sum(array_map(
            static fn (array $line): float => (float) $line['line_extension_amount'],
            $lines
        )), 2);

        // Totales Monetarios Legales
        if (isset($totales['legal_monetary_totals']) && is_array($totales['legal_monetary_totals'])) {
            $payload['legal_monetary_totals'] = $totales['legal_monetary_totals'];
        } else {
            $lineExtTot = $lineExtensionTotal;
            $taxExcl = $lineExtensionTotal;
            $taxIncl = (float)($totales['tax_inclusive_amount'] ?? $totales['total_pagar'] ?? $lineExtTot);
            $payable = (float)($totales['payable_amount'] ?? $taxIncl);

            $payload['legal_monetary_totals'] = [
                'line_extension_amount' => (string)number_format($lineExtTot, 2, '.', ''),
                'tax_exclusive_amount' => (string)number_format($taxExcl, 2, '.', ''),
                'tax_inclusive_amount' => (string)number_format($taxIncl, 2, '.', ''),
                'payable_amount' => $payable,
            ];
        }

        foreach (['line_extension_amount', 'tax_exclusive_amount'] as $baseField) {
            $providedBase = $payload['legal_monetary_totals'][$baseField] ?? null;
            if (!is_numeric($providedBase) || round((float) $providedBase, 2) !== $lineExtensionTotal) {
                throw new \InvalidArgumentException(sprintf(
                    'No se puede construir la factura: legal_monetary_totals.%s debe coincidir con la suma de line_extension_amount (%.2f).',
                    $baseField,
                    $lineExtensionTotal
                ));
            }
            $payload['legal_monetary_totals'][$baseField] = number_format($lineExtensionTotal, 2, '.', '');
        }

        // Los totales de cabecera se agregan de los impuestos ya serializados
        // en las líneas. La base legal se suma una sola vez por línea.
        $payload['tax_totals'] = self::aggregateHeaderTaxTotals($lines);

        MatiasInvoicePayloadValidator::validate($payload);

        return $payload;
    }

    /** Elimina duplicados exactos de tributo dentro de una misma línea. */
    private static function removeDuplicateLineTaxes(array $taxes): array
    {
        $unique = [];
        $seen = [];
        foreach ($taxes as $tax) {
            if (!is_array($tax)) {
                throw new \InvalidArgumentException('Cada componente de tax_totals debe ser un objeto.');
            }
            $taxId = trim((string) ($tax['tax_id'] ?? ''));
            if ($taxId === '') {
                throw new \InvalidArgumentException('No se puede transmitir un impuesto sin tax_id confirmado.');
            }
            foreach (['tax_amount', 'taxable_amount', 'percent'] as $field) {
                if (!isset($tax[$field]) || !is_numeric($tax[$field])) {
                    throw new \InvalidArgumentException("El componente de impuesto requiere {$field} numérico.");
                }
            }

            $signature = implode('|', [
                $taxId,
                number_format((float) $tax['percent'], 4, '.', ''),
                number_format((float) $tax['taxable_amount'], 2, '.', ''),
                number_format((float) $tax['tax_amount'], 2, '.', ''),
            ]);
            if (!isset($seen[$signature])) {
                $seen[$signature] = true;
                $unique[] = [
                    'tax_id' => $taxId,
                    'tax_amount' => number_format((float) $tax['tax_amount'], 2, '.', ''),
                    'taxable_amount' => number_format((float) $tax['taxable_amount'], 2, '.', ''),
                    'percent' => (float) $tax['percent'],
                ];
            }
        }

        return $unique;
    }

    /** Agrega tributos por código y tarifa, contabilizando bases una vez por tributo y línea. */
    private static function aggregateHeaderTaxTotals(array $lines): array
    {
        $totals = [];
        foreach ($lines as $line) {
            $lineGroups = [];
            foreach ($line['tax_totals'] ?? [] as $tax) {
                $taxId = (string) $tax['tax_id'];
                $percent = (float) $tax['percent'];
                $key = $taxId . '|' . number_format($percent, 4, '.', '');
                $signature = number_format((float) $tax['taxable_amount'], 2, '.', '') . '|'
                    . number_format((float) $tax['tax_amount'], 2, '.', '');
                $lineGroups[$key]['tax_id'] = $taxId;
                $lineGroups[$key]['percent'] = $percent;
                $lineGroups[$key]['components'][$signature] = [
                    'taxable_amount' => (float) $tax['taxable_amount'],
                    'tax_amount' => (float) $tax['tax_amount'],
                ];
            }

            foreach ($lineGroups as $key => $group) {
                $lineBases = [];
                $lineTax = 0.0;
                foreach ($group['components'] as $component) {
                    $baseKey = number_format(round($component['taxable_amount'], 2), 2, '.', '');
                    $lineBases[$baseKey] = round($component['taxable_amount'], 2);
                    $lineTax += $component['tax_amount'];
                }
                $lineBase = array_sum($lineBases);
                $totals[$key] ??= [
                    'tax_id' => $group['tax_id'],
                    'taxable_amount' => 0.0,
                    'percent' => $group['percent'],
                    'tax_amount' => 0.0,
                ];
                $totals[$key]['taxable_amount'] += $lineBase;
                $totals[$key]['tax_amount'] += $lineTax;
            }
        }

        foreach ($totals as &$total) {
            $total['taxable_amount'] = number_format(round($total['taxable_amount'], 2), 2, '.', '');
            $total['tax_amount'] = number_format(round($total['tax_amount'], 2), 2, '.', '');
        }
        unset($total);

        return array_values($totals);
    }
}
