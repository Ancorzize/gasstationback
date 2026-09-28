<?php

namespace App\Modules\ResolucionesFacturacion\Application\DTOs;

class CreateResolucionFacturacionDTO
{
    public function __construct(
        public readonly string $tipoDocumento = 'factura',
        public readonly string $numeroResolucion = '',
        public readonly ?string $prefijo = null,
        public readonly ?int $configuracionEmpresaId = null,
        public readonly ?string $fechaResolucion = null,
        public readonly ?int $rangoDesde = null,
        public readonly ?int $rangoHasta = null,
        public readonly int $consecutivoActual = 1,
        public readonly ?string $fechaVencimiento = null,
        public readonly ?string $claveTecnica = null,
        public readonly ?string $proveedor = null,
        public readonly ?string $ambiente = null,
        public readonly bool $isActive = true
    ) {}
}
