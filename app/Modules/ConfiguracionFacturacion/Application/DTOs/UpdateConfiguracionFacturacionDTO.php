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
    ) {}
}
