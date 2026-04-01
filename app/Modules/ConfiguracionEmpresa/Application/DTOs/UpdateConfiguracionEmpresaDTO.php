<?php

namespace App\Modules\ConfiguracionEmpresa\Application\DTOs;

class UpdateConfiguracionEmpresaDTO
{
    public function __construct(
        public string $nombre_empresa,
        public ?string $nombre_comercial,
        public string $nit,
        public ?string $dv,
        public ?string $email,
        public ?string $telefono,
        public ?string $direccion,
        public ?int $pais_id,
        public ?int $departamento_id,
        public ?int $ciudad_id,
        public ?string $logo_url,
        public bool $responsable_iva,
        public ?string $regimen,
        public float $porcentaje_iva,
        public bool $maneja_iva_incluido,
        public ?string $prefijo_factura,
        public ?string $numero_resolucion,
        public ?string $fecha_resolucion,
        public ?int $rango_desde,
        public ?int $rango_hasta,
        public ?string $fecha_vencimiento,
        public string $moneda,
        public string $simbolo_moneda,
        public int $decimales,
    ) {}
}