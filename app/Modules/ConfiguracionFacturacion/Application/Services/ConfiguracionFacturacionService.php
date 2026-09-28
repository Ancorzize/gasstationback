<?php

namespace App\Modules\ConfiguracionFacturacion\Application\Services;

use App\Models\ConfiguracionFacturacion;
use App\Models\ConfiguracionEmpresa;
use App\Modules\ConfiguracionFacturacion\Application\DTOs\UpdateConfiguracionFacturacionDTO;
use App\Modules\ConfiguracionFacturacion\Application\Interfaces\ConfiguracionFacturacionRepositoryInterface;

class ConfiguracionFacturacionService
{
    public function __construct(
        protected ConfiguracionFacturacionRepositoryInterface $repository
    ) {}

    public function get(): ConfiguracionFacturacion
    {
        $config = $this->repository->first();

        if (!$config) {
            $empresa = ConfiguracionEmpresa::first();

            $config = $this->repository->create([
                'configuracion_empresa_id' => $empresa?->id,
                'proveedor_activo' => 'matias',
                'ambiente' => 'sandbox',
                'facturacion_electronica_activa' => false,
                'reintentos_automaticos' => true,
                'max_reintentos' => 3,
            ]);
        }

        return $config;
    }

    public function update(UpdateConfiguracionFacturacionDTO $dto): ConfiguracionFacturacion
    {
        $config = $this->repository->first();

        $empresaId = $dto->configuracion_empresa_id;

        if (!$empresaId) {
            $empresa = ConfiguracionEmpresa::first();
            $empresaId = $empresa?->id;
        }

        $data = [
            'configuracion_empresa_id' => $empresaId,
            'proveedor_activo' => strtolower(trim($dto->proveedor_activo)),
            'ambiente' => $dto->ambiente,
            'facturacion_electronica_activa' => $dto->facturacion_electronica_activa,
            'reintentos_automaticos' => $dto->reintentos_automaticos,
            'max_reintentos' => $dto->max_reintentos,
        ];

        if (!$config) {
            return $this->repository->create($data);
        }

        return $this->repository->update($config, $data);
    }
}
