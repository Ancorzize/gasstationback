<?php

namespace App\Modules\Facturacion\Application\Services;

use App\Modules\Facturacion\Application\DTOs\SolicitudFacturaDTO;
use App\Modules\Facturacion\Application\DTOs\RespuestaFacturacionDTO;
use App\Modules\Facturacion\Infrastructure\Managers\FacturacionManager;
use App\Modules\ConfiguracionFacturacion\Application\Interfaces\ConfiguracionFacturacionRepositoryInterface;

/**
 * Servicio de Aplicación de Facturación Electrónica.
 * Orquesta la obtención de configuración y delega la ejecución al proveedor activo
 * resuelto vía FacturacionManager.
 */
class FacturacionService
{
    public function __construct(
        protected ConfiguracionFacturacionRepositoryInterface $configuracionRepository,
        protected FacturacionManager $facturacionManager
    ) {}

    /**
     * Procesar la emisión de una factura electrónica.
     */
    public function enviarFactura(SolicitudFacturaDTO $solicitud): RespuestaFacturacionDTO
    {
        $config = $this->configuracionRepository->first();

        if (!$config || !$config->facturacion_electronica_activa) {
            return RespuestaFacturacionDTO::error(
                mensaje: "La facturación electrónica se encuentra desactivada en la configuración del ERP.",
                estado: 'desactivado'
            );
        }

        $providerName = $config->proveedor_activo ?? 'matias';
        $ambiente = $config->ambiente ?? 'sandbox';

        $provider = $this->facturacionManager->make($providerName, $ambiente);

        return $provider->enviarFactura($solicitud);
    }

    /**
     * Consultar el estado de un documento electrónico.
     */
    public function consultarEstado(string $identificadorExterno): RespuestaFacturacionDTO
    {
        $config = $this->configuracionRepository->first();

        if (!$config || !$config->facturacion_electronica_activa) {
            return RespuestaFacturacionDTO::error(
                mensaje: "La facturación electrónica se encuentra desactivada en la configuración del ERP.",
                estado: 'desactivado'
            );
        }

        $providerName = $config->proveedor_activo ?? 'matias';
        $ambiente = $config->ambiente ?? 'sandbox';

        $provider = $this->facturacionManager->make($providerName, $ambiente);

        return $provider->consultarEstado($identificadorExterno);
    }
}
