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
        foreach ($lines as $index => $line) {
            if (!is_array($line)) {
                throw new InvalidArgumentException("La línea {$index} no tiene una estructura válida.");
            }

            $lineTaxes = $line['tax_totals'] ?? [];
            if (!is_array($lineTaxes)) {
                throw new InvalidArgumentException("lines[{$index}].tax_totals debe ser un arreglo.");
            }

            foreach ($lineTaxes as $taxIndex => $tax) {
                if (!is_array($tax)) {
                    throw new InvalidArgumentException(
                        "lines[{$index}].tax_totals[{$taxIndex}] no tiene una estructura válida."
                    );
                }

                $lineTaxableAmount += self::amount(
                    $tax['taxable_amount'] ?? null,
                    "lines[{$index}].tax_totals[{$taxIndex}].taxable_amount"
                );
            }
        }

        if (round($headerTaxExclusive, 2) !== round($lineTaxableAmount, 2)) {
            throw new InvalidArgumentException(sprintf(
                'Validación local FAU04: tax_exclusive_amount (%.2f) no coincide con la suma de taxable_amount de las líneas (%.2f). No se infirió ni modificó la clasificación tributaria.',
                $headerTaxExclusive,
                $lineTaxableAmount
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
