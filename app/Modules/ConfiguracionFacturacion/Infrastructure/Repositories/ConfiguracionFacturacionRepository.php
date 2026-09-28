<?php

namespace App\Modules\ConfiguracionFacturacion\Infrastructure\Repositories;

use App\Models\ConfiguracionFacturacion;
use App\Modules\ConfiguracionFacturacion\Application\Interfaces\ConfiguracionFacturacionRepositoryInterface;

class ConfiguracionFacturacionRepository implements ConfiguracionFacturacionRepositoryInterface
{
    public function first(): ?ConfiguracionFacturacion
    {
        return ConfiguracionFacturacion::with('configuracionEmpresa')->first();
    }

    public function create(array $data): ConfiguracionFacturacion
    {
        return ConfiguracionFacturacion::create($data)->load('configuracionEmpresa');
    }

    public function update(ConfiguracionFacturacion $configuracion, array $data): ConfiguracionFacturacion
    {
        $configuracion->update($data);

        return $configuracion->fresh()->load('configuracionEmpresa');
    }
}
