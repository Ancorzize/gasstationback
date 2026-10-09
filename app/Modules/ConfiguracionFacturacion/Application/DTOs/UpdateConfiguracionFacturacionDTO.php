<?php

namespace App\Modules\ConfiguracionFacturacion\Application\DTOs;

class UpdateConfiguracionFacturacionDTO
{
    public function __construct(
        public string $proveedor_activo,
        public string $ambiente,
        public bool $facturacion_electronica_activa,
        public bool $reintentos_automaticos,
        public int $max_reintentos,
        public ?int $configuracion_empresa_id = null,
        public bool $facturar_ventas_pos = true,
        public bool $facturar_ventas_combustible = true,
        public bool $permitir_ventas_sin_datos_fiscales = true,
        public ?string $matias_api_token = null,
        public ?string $matias_sandbox_url = null,
        public ?bool $matias_ds_real_transmission = null,
    ) {}
}
