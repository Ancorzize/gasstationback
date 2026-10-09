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
            'facturar_ventas_pos' => $dto->facturar_ventas_pos,
            'facturar_ventas_combustible' => $dto->facturar_ventas_combustible,
            'permitir_ventas_sin_datos_fiscales' => $dto->permitir_ventas_sin_datos_fiscales,
            'reintentos_automaticos' => $dto->reintentos_automaticos,
            'max_reintentos' => $dto->max_reintentos,
        ];

        if ($dto->matias_sandbox_url !== null) {
            $data['matias_sandbox_url'] = $dto->matias_sandbox_url;
        }

        if ($dto->matias_ds_real_transmission !== null) {
            $data['matias_ds_real_transmission'] = $dto->matias_ds_real_transmission;
        }

        if ($dto->matias_api_token !== null && $dto->matias_api_token !== '********') {
            $data['matias_api_token'] = $dto->matias_api_token;
        }

        if (!$config) {
            return $this->repository->create($data);
        }

        return $this->repository->update($config, $data);
    }
}
