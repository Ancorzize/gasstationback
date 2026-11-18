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
     * Exige homologación activa en mapeos_catalogos_facturacion (categoría 'medio_pago').
     */
    public function obtenerMapeoMedioPago(string $proveedor, string $metodoPago): array
    {
        $metodoNorm = strtolower(trim($metodoPago));
        $proveedorNorm = strtolower(trim($proveedor));

        if ($metodoNorm === '') {
            throw new \InvalidArgumentException(
                "No se especificó un medio de pago válido para homologar con el proveedor '{$proveedor}'."
            );
        }

        $mapeo = MapeoCatalogoFacturacion::where('proveedor', $proveedorNorm)
            ->where('categoria', 'medio_pago')
            ->where('codigo_interno', $metodoNorm)
            ->where('is_active', true)
            ->first();

        if ($mapeo && trim((string) $mapeo->codigo_externo) !== '' && is_numeric(trim((string) $mapeo->codigo_externo))) {
            $defaultPaymentMethodId = $metodoNorm === 'credito' ? 2 : 1;
            $secundario = trim((string) ($mapeo->codigo_externo_secundario ?? ''));

            return [
                'means_payment_id' => (int) trim((string) $mapeo->codigo_externo),
                'payment_method_id' => ($secundario !== '' && is_numeric($secundario))
                    ? (int) $secundario
                    : $defaultPaymentMethodId,
            ];
        }

        throw new \InvalidArgumentException(
            "El medio de pago '{$metodoPago}' no tiene una homologación activa configurada para el proveedor '{$proveedor}' en el catálogo de facturación electrónica (categoría 'medio_pago')."
        );
    }

    /**
     * Resolver el código de tributo MATÍAS/DIAN para un producto según su clasificación tributaria.
     * Reutiliza mapeos_catalogos_facturacion (categoría 'impuesto') y respalda IVA con código '1'.
     */
    public function resolverCodigoImpuestoProducto(
        string $tipoImpuesto,
        ?string $codigoSolicitado = null,
        string $proveedor = 'matias'
    ): ?string {
        $tipoNormalized = strtolower(trim($tipoImpuesto));

        if ($tipoNormalized === \App\Models\Producto::TIPO_IMPUESTO_EXCLUIDO) {
            return null;
        }

        if (!in_array($tipoNormalized, [
            \App\Models\Producto::TIPO_IMPUESTO_GRAVADO,
            \App\Models\Producto::TIPO_IMPUESTO_EXENTO,
        ], true)) {
            throw new \InvalidArgumentException("Clasificación tributaria '{$tipoImpuesto}' no reconocida.");
        }

        $solicitado = trim((string) ($codigoSolicitado ?? ''));
        if ($solicitado !== '') {
            $mapeoPorInterno = MapeoCatalogoFacturacion::where('proveedor', strtolower(trim($proveedor)))
                ->where('categoria', 'impuesto')
                ->where('codigo_interno', strtolower($solicitado))
                ->where('is_active', true)
                ->first();

            if ($mapeoPorInterno) {
                return trim((string) $mapeoPorInterno->codigo_externo);
            }

            $mapeoPorExterno = MapeoCatalogoFacturacion::where('proveedor', strtolower(trim($proveedor)))
                ->where('categoria', 'impuesto')
                ->where('codigo_externo', $solicitado)
                ->where('is_active', true)
                ->first();

            if ($mapeoPorExterno) {
                return trim((string) $mapeoPorExterno->codigo_externo);
            }

            if (in_array(strtolower($solicitado), ['1', '01', 'iva'], true)) {
                return '1';
            }

            throw new \InvalidArgumentException(
                "El código de impuesto '{$solicitado}' no corresponde a una homologación activa en el catálogo de facturación."
            );
        }

        $codigoCatalogo = $this->obtenerCodigoExterno($proveedor, 'impuesto', 'iva')
            ?? $this->obtenerCodigoExterno($proveedor, 'impuesto', $tipoNormalized)
            ?? '1';

        return trim($codigoCatalogo) !== '' ? trim($codigoCatalogo) : '1';
    }

    /**
     * Resolver unidad de medida homologada (quantity_units_id y um) a partir de UnidadMedida del producto.
     * Exige homologación válida en mapeos_catalogos_facturacion o código DIAN numérico explícito en la unidad.
     */
    public function resolverUnidadMedidaProducto(
        ?\App\Models\UnidadMedida $unidad,
        string $proveedor = 'matias'
    ): array {
        if (!$unidad) {
            throw new \InvalidArgumentException(
                'El producto no tiene una unidad de medida asignada. Configure su unidad de medida antes de emitir el documento electrónico.'
            );
        }

        $proveedorNorm = strtolower(trim($proveedor));
        $candidates = array_values(array_unique(array_filter([
            $unidad->abreviatura ? strtolower(trim($unidad->abreviatura)) : null,
            $unidad->codigo_dian ? strtolower(trim($unidad->codigo_dian)) : null,
            $unidad->nombre ? strtolower(trim($unidad->nombre)) : null,
            $unidad->id ? (string) $unidad->id : null,
        ])));

        foreach ($candidates as $candidate) {
            $mapeo = MapeoCatalogoFacturacion::where('proveedor', $proveedorNorm)
                ->where('categoria', 'unidad_medida')
                ->where('codigo_interno', $candidate)
                ->where('is_active', true)
                ->first();

            if ($mapeo && trim((string) $mapeo->codigo_externo) !== '') {
                $umHomologada = trim((string) (
                    $mapeo->codigo_externo_secundario
                    ?: ($unidad->simbolo_dian ?: $unidad->abreviatura)
                ));

                if ($umHomologada === '') {
                    throw new \InvalidArgumentException(
                        "El mapeo de la unidad de medida '{$unidad->nombre}' ({$unidad->abreviatura}) no define un símbolo/abreviatura (um) válido."
                    );
                }

                return [
                    'quantity_units_id' => trim((string) $mapeo->codigo_externo),
                    'um' => $umHomologada,
                ];
            }
        }

        $codigoDian = trim((string) ($unidad->codigo_dian ?? ''));
        $simboloDian = trim((string) ($unidad->simbolo_dian ?? ''));
        $abreviatura = trim((string) ($unidad->abreviatura ?? ''));
        $umFallback = $simboloDian !== '' ? $simboloDian : $abreviatura;

        if ($codigoDian !== '' && is_numeric($codigoDian) && $umFallback !== '') {
            return [
                'quantity_units_id' => $codigoDian,
                'um' => $umFallback,
            ];
        }

        $etiquetaUnidad = trim($unidad->nombre . ($abreviatura !== '' ? " ({$abreviatura})" : ''));

        throw new \InvalidArgumentException(
            "La unidad de medida '{$etiquetaUnidad}' no tiene una homologación activa configurada para el proveedor '{$proveedor}' en el catálogo de facturación electrónica (categoría 'unidad_medida')."
        );
    }
}
