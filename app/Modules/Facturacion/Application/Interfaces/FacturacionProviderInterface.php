<?php

namespace App\Modules\Facturacion\Application\Interfaces;

use App\Modules\Facturacion\Application\DTOs\SolicitudFacturaDTO;
use App\Modules\Facturacion\Application\DTOs\RespuestaFacturacionDTO;

/**
 * Interfaz genérica para proveedores de facturación electrónica.
 * Independiente de cualquier proveedor específico (MATIAS, etc.).
 */
interface FacturacionProviderInterface
{
    /**
     * Enviar una solicitud de factura electrónica al proveedor.
     */
    public function enviarFactura(SolicitudFacturaDTO $solicitud): RespuestaFacturacionDTO;

    /**
     * Consultar el estado de un documento electrónico por su identificador externo.
     */
    public function consultarEstado(string $identificadorExterno): RespuestaFacturacionDTO;
}
