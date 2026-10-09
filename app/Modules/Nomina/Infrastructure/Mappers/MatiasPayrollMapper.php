<?php

namespace App\Modules\Nomina\Infrastructure\Mappers;

use App\Models\PayrollSettlement;
use App\Modules\Nomina\Domain\Enums\PayrollDeductionTypeEnum;
use App\Modules\Nomina\Domain\Enums\PayrollEarningTypeEnum;

/**
 * Mapper encargado de transformar la entidad interna PayrollSettlement de GASSTATION
 * al payload JSON requerido por la API de MATIAS para Nómina Electrónica (POST /ep/payroll).
 */
class MatiasPayrollMapper
{
    /**
     * Mapear un PayrollSettlement al payload asociativo exigido por MATIAS API (POST /ep/payroll).
     *
     * @param PayrollSettlement $settlement Liquidación de nómina con sus relaciones (payrollPeriod, employee, earnings, deductions).
     * @param array $options Opciones y metadatos adicionales (resolution_number, prefix, document_number, issue_date, etc.).
     * @return array Payload estructurado para MATIAS API.
     */
    public static function toMatiasPayload(PayrollSettlement $settlement, array $options = []): array
    {
        // 1. Información del Período de Nómina
        $period = $settlement->payrollPeriod;
        $startDate = $period?->start_date ? $period->start_date->format('Y-m-d') : ($options['start_date'] ?? '');
        $endDate = $period?->end_date ? $period->end_date->format('Y-m-d') : ($options['end_date'] ?? '');
        $paymentDate = $period?->payment_date ? $period->payment_date->format('Y-m-d') : ($options['payment_date'] ?? '');
        $issueDate = $options['issue_date'] ?? date('Y-m-d');

        // 2. Información del Empleado (Snapshot de la liquidación + relaciones)
        $employeeModel = $settlement->employee;
        $dateEntry = $settlement->date_entry ? $settlement->date_entry->format('Y-m-d') : '';
        $departureDate = $settlement->departure_date ? $settlement->departure_date->format('Y-m-d') : null;

        $employeePayload = [
            'identity_document_id' => (string) $settlement->identity_document_id,
            'document_number'      => (string) $settlement->document_number,
            'dni'                  => (string) $settlement->document_number,
            'first_name'           => (string) $settlement->first_name,
            'other_names'          => (string) ($settlement->other_names ?? ''),
            'first_surname'        => (string) $settlement->first_surname,
            'second_surname'       => (string) ($settlement->second_surname ?? ''),
            'worker_type_id'       => (int) $settlement->worker_type_id,
            'worker_subtype_id'    => (int) $settlement->worker_subtype_id,
            'contract_type_id'     => (int) $settlement->contract_type_id,
            'high_risk_pension'    => (bool) ($employeeModel?->high_risk_pension ?? false),
            'date_entry'           => $dateEntry,
            'departure_date'       => $departureDate,
            'salary'               => (string) number_format((float) $settlement->salary, 2, '.', ''),
            'integral_salary'      => (bool) $settlement->integral_salary,
        ];

        // 3. Método y Cuenta de Pago
        $activeAccount = $employeeModel?->paymentAccounts?->firstWhere('is_active', true) 
            ?? $employeeModel?->paymentAccounts?->first();

        $paymentPayload = [
            'payment_method_id' => (string) ($activeAccount?->payment_method_id ?? $options['payment_method_id'] ?? '1'),
            'means_payment_id'  => (string) ($activeAccount?->means_payment_id ?? $options['means_payment_id'] ?? '10'),
            'bank'              => (string) ($activeAccount?->bank ?? $options['bank'] ?? ''),
            'account_type'      => (string) ($activeAccount?->account_type ?? $options['account_type'] ?? ''),
            'account_number'    => (string) ($activeAccount?->account_number ?? $options['account_number'] ?? ''),
        ];

        // 4. Devengados (earn)
        $earnPayload = [];

        // Devengado obligatorio: Salario Básico
        $basicEarning = $settlement->earnings?->firstWhere('type_code', PayrollEarningTypeEnum::BASIC_SALARY->value);
        $workedDays = $basicEarning ? (int) $basicEarning->quantity : (int) $settlement->days_worked;
        $basicAmount = $basicEarning ? (float) $basicEarning->amount : (float) $settlement->basic_salary;

        $earnPayload['basic_salary'] = [
            'worked_days' => $workedDays,
            'amount'      => (string) number_format($basicAmount, 2, '.', ''),
        ];

        // Devengado opcional: Auxilio de Transporte
        $transportEarning = $settlement->earnings?->firstWhere('type_code', PayrollEarningTypeEnum::TRANSPORT_ASSISTANCE->value);
        if ($transportEarning) {
            $earnPayload['transport_assistance'] = [
                'amount' => (string) number_format((float) $transportEarning->amount, 2, '.', ''),
            ];
        }

        // 5. Deducciones (deductions)
        $deductionsPayload = [];

        // Deducción: Salud (4%)
        $healthDeduction = $settlement->deductions?->firstWhere('type_code', PayrollDeductionTypeEnum::HEALTH->value);
        if ($healthDeduction) {
            $deductionsPayload['health'] = [
                'percentage' => (string) number_format((float) $healthDeduction->percentage, 2, '.', ''),
                'deduction'  => (string) number_format((float) $healthDeduction->amount, 2, '.', ''),
            ];
        }

        // Deducción: Pensión (4%)
        $pensionDeduction = $settlement->deductions?->firstWhere('type_code', PayrollDeductionTypeEnum::PENSION->value);
        if ($pensionDeduction) {
            $deductionsPayload['pension_fund'] = [
                'percentage' => (string) number_format((float) $pensionDeduction->percentage, 2, '.', ''),
                'deduction'  => (string) number_format((float) $pensionDeduction->amount, 2, '.', ''),
            ];
        }

        // 6. Información General (general_information)
        $resolutionNumber = (string) ($options['resolution_number'] ?? $options['resolution'] ?? '');
        $documentNumberStr = (string) ($options['document_number'] ?? $settlement->id);

        $generalInformation = [
            'environment_id'     => (string) ($options['environment_id'] ?? $options['ambiente_id'] ?? '2'),
            'type_document_id'   => (string) ($options['type_document_id'] ?? '9'),
            'establishment_code' => (string) ($options['establishment_code'] ?? '1'),
            'prefix'             => (string) ($options['prefix'] ?? $options['prefijo'] ?? ''),
            'document_number'    => $documentNumberStr,
            'notes'              => (string) ($options['notes'] ?? ('Nómina Electrónica Período ' . ($period?->name ?? ''))),
            'novelty'            => (bool) ($options['novelty'] ?? false),
        ];

        // 7. Período de Nómina (period)
        $periodPayload = [
            'date_entry'            => $dateEntry,
            'settlement_start_date' => $startDate,
            'settlement_end_date'   => $endDate,
            'issue_date'            => $issueDate,
            'payment_date'          => $paymentDate,
            'time_worked'           => (string) $workedDays,
        ];

        // 8. Ensamblar Payload final MATIAS
        $payload = [
            'resolution_number'   => $resolutionNumber,
            'document_number'     => $documentNumberStr,
            'general_information' => $generalInformation,
            'period'              => $periodPayload,
            'worker_code'         => (string) $settlement->worker_code,
            'employee'            => $employeePayload,
            'payment'             => $paymentPayload,
            'earn'                => $earnPayload,
            'deductions'          => $deductionsPayload,
            'total_earned'        => (string) number_format((float) $settlement->total_earned, 2, '.', ''),
            'deductions_total'    => (string) number_format((float) $settlement->deductions_total, 2, '.', ''),
            'total_voucher'       => (string) number_format((float) $settlement->total_voucher, 2, '.', ''),
        ];

        if (isset($options['replacing_predecessor']) && is_array($options['replacing_predecessor'])) {
            $payload['replacing_predecessor'] = $options['replacing_predecessor'];
        }

        return $payload;
    }
}
