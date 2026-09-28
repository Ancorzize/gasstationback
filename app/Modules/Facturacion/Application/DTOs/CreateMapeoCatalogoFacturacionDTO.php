<?php

namespace App\Modules\Facturacion\Application\DTOs;

class CreateMapeoCatalogoFacturacionDTO
{
    public function __construct(
        public string $proveedor,
        public string $categoria,
        public string $codigo_interno,
        public string $codigo_externo,
        public ?string $codigo_externo_secundario = null,
        public ?string $descripcion = null,
        public bool $is_active = true,
    ) {}
}
