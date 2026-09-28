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
        public ?string $tipo_persona = null,
        public ?string $tipo_documento = null,
        public ?int $pais_id = null,
        public ?int $departamento_id = null,
        public ?int $ciudad_id = null,
        public ?string $codigo_postal = null,
        public ?string $matricula_mercantil = null,
        public ?string $logo_url = null,
        public bool $responsable_iva = true,
        public ?string $regimen = null,
        public ?string $tipo_regimen = null,
        public ?array $responsabilidades_fiscales = null,
        public float $porcentaje_iva = 0,
        public bool $maneja_iva_incluido = false,
        public ?string $prefijo_factura = null,
        public ?string $numero_resolucion = null,
        public ?string $fecha_resolucion = null,
        public ?int $rango_desde = null,
        public ?int $rango_hasta = null,
        public ?string $fecha_vencimiento = null,
        public string $moneda = 'COP',
        public string $simbolo_moneda = '$',
        public int $decimales = 2,
    ) {}
}