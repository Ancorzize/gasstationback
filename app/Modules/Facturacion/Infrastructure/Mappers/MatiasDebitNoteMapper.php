<?php

namespace App\Modules\Facturacion\Infrastructure\Mappers;

use App\Modules\Facturacion\Application\DTOs\SolicitudNotaDebitoDTO;
use InvalidArgumentException;

/**
 * Mapper encuadrador del DTO SolicitudNotaDebitoDTO 
 * hacia el payload JSON estandarizado de MATIAS API (POST /notes/debit).
 */
class MatiasDebitNoteMapper
{
    /**
     * Mapear una SolicitudNotaDebitoDTO al arreglo asociativo exigido por MATIAS API.
     */
    public static function toMatiasPayload(SolicitudNotaDebitoDTO $solicitud): array
    {
        $meta = $solicitud->metadatos;
        $cliente = $solicitud->cliente;
        $emisor = $solicitud->emisor;
        $totales = $solicitud->totales;
        $discrepancy = $solicitud->discrepancyResponse;
        $billingRef = $solicitud->billingReference;

        $rawDocNumber = $solicitud->folio ?? $solicitud->ventaId ?? null;
        if ($rawDocNumber === null || $rawDocNumber === '' || !is_numeric($rawDocNumber)) {
            throw new InvalidArgumentException("El campo document_number de la Nota Débito debe ser un número entero válido.");
        }

        $payload = [
            'resolution_number' => (string)($meta['resolution_number'] ?? $emisor['resolution_number'] ?? ''),
            'prefix' => (string)($solicitud->prefijo ?? $meta['prefix'] ?? 'ND'),
            'notes' => (string)($solicitud->observacion ?? $meta['notes'] ?? 'Nota Débito Electrónica'),
            'document_number' => (int) $rawDocNumber,
            'graphic_representation' => (int)($meta['graphic_representation'] ?? 0),
            'send_email' => (int)($meta['send_email'] ?? 1),
            'operation_type_id' => (int)($meta['operation_type_id'] ?? 1),
            'type_document_id' => (int)($meta['type_document_id'] ?? 4),
        ];

        // Discrepancy Response (Motivo y referencia de ajuste)
        $refId = (string)($discrepancy['reference_id'] ?? $billingRef['uuid'] ?? '');
        $respId = (int)($discrepancy['response_id'] ?? 1); // 1 = Intereses, 2 = Gastos por cobrar, 3 = Cambio del valor, 4 = Otros
        $desc = (string)($discrepancy['description'] ?? $solicitud->observacion ?? 'Ajuste de débito en factura electrónica');

        $payload['discrepancy_response'] = [
            'reference_id' => $refId,
            'response_id' => $respId,
            'description' => $desc,
        ];

        // Billing Reference (Datos de la factura original)
        $payload['billing_reference'] = [
            'number' => (string)($billingRef['number'] ?? ''),
            'date' => (string)($billingRef['date'] ?? date('Y-m-d')),
            'uuid' => (string)($billingRef['uuid'] ?? $refId),
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

        // Mapeo de Cliente / Receptor
        $rawDni = (string)($cliente['dni'] ?? $cliente['numero_documento'] ?? '222222222222');
        $cleanDni = preg_replace('/[^a-zA-Z0-9]/', '', $rawDni);

        $payload['customer'] = MatiasCustomerMapper::toMatiasCustomer([
            'identity_document_id' => (string)($cliente['identity_document_id'] ?? $cliente['tipo_documento_id'] ?? '1'),
            'type_organization_id' => (int)($cliente['type_organization_id'] ?? $cliente['tipo_persona_id'] ?? 2),
            'tax_regime_id' => (int)($cliente['tax_regime_id'] ?? $cliente['tipo_regimen_id'] ?? 2),
            'tax_level_id' => (int)($cliente['tax_level_id'] ?? 5),
            'company_name' => (string)($cliente['company_name'] ?? $cliente['nombre_razon_social'] ?? 'CLIENTE MOSTRADOR'),
            'dni' => !empty($cleanDni) ? $cleanDni : '222222222222',
            'mobile' => (string)($cliente['mobile'] ?? $cliente['telefono'] ?? '3000000000'),
            'email' => (string)($cliente['email'] ?? 'factura@cliente.com'),
            'address' => (string)($cliente['address'] ?? $cliente['direccion'] ?? ''),
            'postal_code' => (string)($cliente['postal_code'] ?? ''),
            'country_iso' => $cliente['country_iso'] ?? null,
            'city_dane_code' => $cliente['city_dane_code'] ?? null,
            'city_local_id' => $cliente['city_local_id'] ?? null,
        ]);

        // Mapeo de Líneas de Producto / Servicio
        $lines = [];
        foreach ($solicitud->items as $indice => $item) {
            $cant = (float)($item['invoiced_quantity'] ?? $item['cantidad'] ?? 1);
            $precio = (float)($item['price_amount'] ?? $item['precio_unitario'] ?? 0);
            $lineExt = round((float)($item['line_extension_amount'] ?? ($cant * $precio)), 2);
            $tipoImpuesto = isset($item['tipo_impuesto']) && is_string($item['tipo_impuesto'])
                ? strtolower(trim($item['tipo_impuesto']))
                : null;

            $itemTaxes = [];
            if (array_key_exists('tax_totals', $item) && is_array($item['tax_totals'])) {
                $itemTaxes = $item['tax_totals'];
            } elseif ($tipoImpuesto !== null) {
                $taxAmount = round((float)($item['tax_amount'] ?? 0), 2);
                $percent = round((float)($item['percent'] ?? 0), 2);
                $taxId = trim((string)($item['tax_id'] ?? $item['codigo_impuesto'] ?? '1'));

                if ($tipoImpuesto === \App\Models\Producto::TIPO_IMPUESTO_GRAVADO) {
                    if ($percent <= 0) {
                        throw new InvalidArgumentException(sprintf(
                            'No se puede construir tax_totals en la línea %d de Nota Débito: un producto gravado requiere tarifa mayor a 0%%.',
                            $indice + 1
                        ));
                    }
                    $itemTaxes[] = [
                        'tax_id' => $taxId !== '' ? $taxId : '1',
                        'tax_amount' => number_format($taxAmount, 2, '.', ''),
                        'taxable_amount' => number_format($lineExt, 2, '.', ''),
                        'percent' => $percent,
                    ];
                } elseif ($tipoImpuesto === \App\Models\Producto::TIPO_IMPUESTO_EXENTO) {
                    $itemTaxes[] = [
                        'tax_id' => $taxId !== '' ? $taxId : '1',
                        'tax_amount' => '0.00',
                        'taxable_amount' => number_format($lineExt, 2, '.', ''),
                        'percent' => 0.0,
                    ];
                } elseif ($tipoImpuesto === \App\Models\Producto::TIPO_IMPUESTO_EXCLUIDO) {
                    $itemTaxes = [];
                } else {
                    throw new InvalidArgumentException(sprintf(
                        'Clasificación tributaria no reconocida en la línea %d de Nota Débito: %s.',
                        $indice + 1,
                        $tipoImpuesto
                    ));
                }
            } else {
                $taxAmount = round((float)($item['tax_amount'] ?? 0), 2);
                $percent = round((float)($item['percent'] ?? 0), 2);
                if ($taxAmount > 0 || $percent > 0) {
                    $itemTaxes[] = [
                        'tax_id' => (string)($item['tax_id'] ?? $item['codigo_impuesto'] ?? '1'),
                        'tax_amount' => number_format($taxAmount, 2, '.', ''),
                        'taxable_amount' => number_format($lineExt, 2, '.', ''),
                        'percent' => $percent,
                    ];
                }
            }

            $formattedQty = self::formatQuantity($cant);

            $lineData = [
                'invoiced_quantity' => $formattedQty,
                'quantity_units_id' => (string)($item['quantity_units_id'] ?? '1093'),
                'line_extension_amount' => (string)number_format($lineExt, 2, '.', ''),
                'free_of_charge_indicator' => (bool)($item['free_of_charge_indicator'] ?? false),
                'description' => (string)($item['description'] ?? $item['nombre'] ?? 'Producto / Servicio'),
                'code' => (string)($item['code'] ?? $item['codigo'] ?? 'PROD'),
                'type_item_identifications_id' => (string)($item['type_item_identifications_id'] ?? '4'),
                'reference_price_id' => (string)($item['reference_price_id'] ?? '1'),
                'price_amount' => (string)number_format($precio, 2, '.', ''),
                'base_quantity' => $formattedQty,
                'um' => (string)($item['um'] ?? 'M'),
                'tax_totals' => $itemTaxes,
            ];

            $lines[] = $lineData;
        }
        $payload['lines'] = $lines;

        $lineExtensionTotal = round(array_sum(array_map(
            static fn (array $line): float => (float) $line['line_extension_amount'],
            $lines
        )), 2);
        $lineTaxableTotal = self::calculateTotalTaxableBaseFromLines($lines);

        // Totales Monetarios Legales
        if (isset($totales['legal_monetary_totals']) && is_array($totales['legal_monetary_totals'])) {
            $payload['legal_monetary_totals'] = $totales['legal_monetary_totals'];
        } else {
            $lineExtTot = (float)($totales['line_extension_amount'] ?? $totales['subtotal'] ?? $lineExtensionTotal);
            $taxExcl = isset($totales['tax_exclusive_amount']) && is_numeric($totales['tax_exclusive_amount'])
                ? (float) $totales['tax_exclusive_amount']
                : $lineTaxableTotal;
            $taxIncl = (float)($totales['tax_inclusive_amount'] ?? $totales['total_pagar'] ?? $lineExtTot);
            $payable = (float)($totales['payable_amount'] ?? $taxIncl);

            $payload['legal_monetary_totals'] = [
                'line_extension_amount' => (string)number_format($lineExtTot, 2, '.', ''),
                'tax_exclusive_amount' => (string)number_format($taxExcl, 2, '.', ''),
                'tax_inclusive_amount' => (string)number_format($taxIncl, 2, '.', ''),
                'payable_amount' => (string)number_format($payable, 2, '.', ''),
            ];
        }

        // Totales de Impuestos Generales
        if (isset($totales['tax_totals']) && is_array($totales['tax_totals'])) {
            $payload['tax_totals'] = $totales['tax_totals'];
        } elseif (isset($meta['tax_totals']) && is_array($meta['tax_totals'])) {
            $payload['tax_totals'] = $meta['tax_totals'];
        } else {
            $payload['tax_totals'] = self::aggregateHeaderTaxTotals($lines);
        }

        \App\Modules\Facturacion\Infrastructure\Validators\MatiasInvoicePayloadValidator::validate($payload);

        return $payload;
    }

    private static function formatQuantity(float $quantity): string
    {
        $rounded3 = round($quantity, 3);
        if (abs($rounded3 - round($rounded3, 2)) > 0.0001) {
            return number_format($rounded3, 3, '.', '');
        }

        return number_format($rounded3, 2, '.', '');
    }

    private static function calculateTotalTaxableBaseFromLines(array $lines): float
    {
        $totalTaxable = 0.0;
        foreach ($lines as $line) {
            $uniqueBases = [];
            foreach ($line['tax_totals'] ?? [] as $tax) {
                if (isset($tax['taxable_amount']) && is_numeric($tax['taxable_amount'])) {
                    $baseVal = round((float) $tax['taxable_amount'], 2);
                    $baseKey = number_format($baseVal, 2, '.', '');
                    $uniqueBases[$baseKey] = $baseVal;
                }
            }
            $totalTaxable += array_sum($uniqueBases);
        }

        return round($totalTaxable, 2);
    }

    private static function aggregateHeaderTaxTotals(array $lines): array
    {
        $totals = [];
        foreach ($lines as $line) {
            foreach ($line['tax_totals'] ?? [] as $tax) {
                $taxId = (string) ($tax['tax_id'] ?? '1');
                $percent = (float) ($tax['percent'] ?? 0);
                $key = $taxId . '|' . number_format($percent, 4, '.', '');
                $totals[$key] ??= [
                    'tax_id' => $taxId,
                    'taxable_amount' => 0.0,
                    'percent' => $percent,
                    'tax_amount' => 0.0,
                ];
                $totals[$key]['taxable_amount'] += round((float) ($tax['taxable_amount'] ?? 0), 2);
                $totals[$key]['tax_amount'] += round((float) ($tax['tax_amount'] ?? 0), 2);
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
