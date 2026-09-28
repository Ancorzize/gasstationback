<?php

namespace App\Modules\Facturacion\Application\Services;

use App\Models\MapeoCatalogoFacturacion;

/**
 * Servicio de Aplicación para consultar y resolver equivalencias de catálogos fiscales ERP -> Proveedor.
 */
class CatalogosFacturacionService
{
    /**
     * Obtener el código externo configurado para una categoría y código interno ERP.
     */
    public function obtenerCodigoExterno(
        string $proveedor,
        string $categoria,
        string $codigoInterno,
        ?string $default = null
    ): ?string {
        $mapeo = MapeoCatalogoFacturacion::where('proveedor', strtolower(trim($proveedor)))
            ->where('categoria', strtolower(trim($categoria)))
            ->where('codigo_interno', strtolower(trim($codigoInterno)))
            ->where('is_active', true)
            ->first();

        return $mapeo ? $mapeo->codigo_externo : $default;
    }

    /**
     * Obtener el mapeo completo (código principal y secundario) para un medio de pago ERP.
     */
    public function obtenerMapeoMedioPago(string $proveedor, string $metodoPago): array
    {
        $mapeo = MapeoCatalogoFacturacion::where('proveedor', strtolower(trim($proveedor)))
            ->where('categoria', 'medio_pago')
            ->where('codigo_interno', strtolower(trim($metodoPago)))
            ->where('is_active', true)
            ->first();

        if ($mapeo) {
            return [
                'means_payment_id' => (int)$mapeo->codigo_externo,
                'payment_method_id' => (int)($mapeo->codigo_externo_secundario ?? 1),
            ];
        }

        // Fallbacks por defecto si no existe registro en base de datos
        $paymentMethodId = strtolower(trim($metodoPago)) === 'credito' ? 2 : 1;
        $meansPaymentId = match (strtolower(trim($metodoPago))) {
            'efectivo' => 10,
            'transferencia', 'qr' => 47,
            'datafono' => 48,
            'consignacion' => 42,
            default => 10,
        };

        return [
            'means_payment_id' => $meansPaymentId,
            'payment_method_id' => $paymentMethodId,
        ];
    }
}
