<?php

namespace App\Modules\Facturacion\Infrastructure\Validators;

use InvalidArgumentException;

/** Validaciones aritméticas que no asignan ni infieren tratamientos tributarios. */
class MatiasInvoicePayloadValidator
{
    public static function validate(array $payload): void
    {
        $legalTotals = $payload['legal_monetary_totals'] ?? null;
        $lines = $payload['lines'] ?? null;

        if (!is_array($legalTotals) || !is_array($lines)) {
            throw new InvalidArgumentException(
                'No se puede validar FAU04: faltan legal_monetary_totals o lines.'
            );
        }

        $headerTaxExclusive = self::amount(
            $legalTotals['tax_exclusive_amount'] ?? null,
            'legal_monetary_totals.tax_exclusive_amount'
        );

        $lineTaxableAmount = 0.0;
        $lineExtensionAmount = 0.0;
        $lineTaxAmount = 0.0;
        foreach ($lines as $index => $line) {
            if (!is_array($line)) {
                throw new InvalidArgumentException("La línea {$index} no tiene una estructura válida.");
            }

            $lineExtension = self::amount(
                $line['line_extension_amount'] ?? null,
                "lines[{$index}].line_extension_amount"
            );
            $lineExtensionAmount += $lineExtension;

            $lineTaxes = $line['tax_totals'] ?? [];
            if (!is_array($lineTaxes)) {
                throw new InvalidArgumentException("lines[{$index}].tax_totals debe ser un arreglo.");
            }

            $uniqueBasesForLine = [];
            $uniqueTaxesForLine = [];
            foreach ($lineTaxes as $taxIndex => $tax) {
                if (!is_array($tax)) {
                    throw new InvalidArgumentException(
                        "lines[{$index}].tax_totals[{$taxIndex}] no tiene una estructura válida."
                    );
                }

                $taxableAmount = self::amount(
                    $tax['taxable_amount'] ?? null,
                    "lines[{$index}].tax_totals[{$taxIndex}].taxable_amount"
                );
                $taxAmount = self::amount(
                    $tax['tax_amount'] ?? null,
                    "lines[{$index}].tax_totals[{$taxIndex}].tax_amount"
                );
                $percent = self::amount(
                    $tax['percent'] ?? null,
                    "lines[{$index}].tax_totals[{$taxIndex}].percent"
                );
                $taxId = trim((string) ($tax['tax_id'] ?? ''));
                if ($taxId === '') {
                    throw new InvalidArgumentException("lines[{$index}].tax_totals[{$taxIndex}].tax_id es obligatorio.");
                }

                // FAU04 compara la base de la línea una sola vez, aunque varios
                // tributos de esa misma línea compartan idéntico taxable_amount.
                $baseKey = number_format(round($taxableAmount, 2), 2, '.', '');
                $uniqueBasesForLine[$baseKey] = round($taxableAmount, 2);

                $taxSignature = implode('|', [
                    $taxId,
                    number_format($percent, 4, '.', ''),
                    $baseKey,
                    number_format(round($taxAmount, 2), 2, '.', ''),
                ]);
                $uniqueTaxesForLine[$taxSignature] = round($taxAmount, 2);
            }

            if ($lineExtension > 0 && $uniqueBasesForLine === []) {
                throw new InvalidArgumentException(
                    "No se puede validar FAU04: lines[{$index}] tiene base positiva y no contiene tax_totals; falta clasificación tributaria explícita."
                );
            }

            $lineTaxableAmount += array_sum($uniqueBasesForLine);
            $lineTaxAmount += array_sum($uniqueTaxesForLine);

            if (round(array_sum($uniqueBasesForLine), 2) !== round($lineExtension, 2)) {
                throw new InvalidArgumentException(sprintf(
                    'La base imponible única de la línea %d (%.2f) no coincide con line_extension_amount (%.2f).',
                    $index,
                    array_sum($uniqueBasesForLine),
                    $lineExtension
                ));
            }
        }

        $headerLineExtension = self::amount(
            $legalTotals['line_extension_amount'] ?? null,
            'legal_monetary_totals.line_extension_amount'
        );
        if (round($headerLineExtension, 2) !== round($lineExtensionAmount, 2)) {
            throw new InvalidArgumentException(sprintf(
                'legal_monetary_totals.line_extension_amount (%.2f) no coincide con la suma de line_extension_amount de líneas (%.2f).',
                $headerLineExtension,
                $lineExtensionAmount
            ));
        }

        if (round($headerTaxExclusive, 2) !== round($lineTaxableAmount, 2)) {
            throw new InvalidArgumentException(sprintf(
                'Validación local FAU04: tax_exclusive_amount (%.2f) no coincide con la suma de taxable_amount de las líneas (%.2f). No se infirió ni modificó la clasificación tributaria.',
                $headerTaxExclusive,
                $lineTaxableAmount
            ));
        }

        $headerTaxAmount = 0.0;
        foreach ($payload['tax_totals'] ?? [] as $taxIndex => $tax) {
            if (!is_array($tax)) {
                throw new InvalidArgumentException("tax_totals[{$taxIndex}] no tiene una estructura válida.");
            }
            $headerTaxAmount += self::amount(
                $tax['tax_amount'] ?? null,
                "tax_totals[{$taxIndex}].tax_amount"
            );
        }
        if (round($headerTaxAmount, 2) !== round($lineTaxAmount, 2)) {
            throw new InvalidArgumentException(sprintf(
                'La suma de impuestos de cabecera (%.2f) no coincide con los impuestos deduplicados de líneas (%.2f).',
                $headerTaxAmount,
                $lineTaxAmount
            ));
        }

        $taxInclusive = self::amount(
            $legalTotals['tax_inclusive_amount'] ?? null,
            'legal_monetary_totals.tax_inclusive_amount'
        );
        if (round($taxInclusive, 2) !== round($headerTaxExclusive + $headerTaxAmount, 2)) {
            throw new InvalidArgumentException(sprintf(
                'tax_inclusive_amount (%.2f) no coincide con base más impuestos (%.2f).',
                $taxInclusive,
                $headerTaxExclusive + $headerTaxAmount
            ));
        }

        $payableAmount = self::amount(
            $legalTotals['payable_amount'] ?? null,
            'legal_monetary_totals.payable_amount'
        );
        if (round($payableAmount, 2) !== round($taxInclusive, 2)) {
            throw new InvalidArgumentException(sprintf(
                'payable_amount (%.2f) no coincide con tax_inclusive_amount (%.2f).',
                $payableAmount,
                $taxInclusive
            ));
        }
    }

    private static function amount(mixed $value, string $field): float
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException("El campo {$field} debe contener un valor numérico.");
        }

        return (float) $value;
    }
}
