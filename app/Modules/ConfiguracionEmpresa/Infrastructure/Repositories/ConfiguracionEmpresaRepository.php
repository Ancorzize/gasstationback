<?php

namespace App\Modules\ConfiguracionEmpresa\Infrastructure\Repositories;

use App\Models\ConfiguracionEmpresa;
use App\Modules\ConfiguracionEmpresa\Application\Interfaces\ConfiguracionEmpresaRepositoryInterface;

class ConfiguracionEmpresaRepository implements ConfiguracionEmpresaRepositoryInterface
{
    public function first(): ?ConfiguracionEmpresa
    {
        return ConfiguracionEmpresa::with(['pais', 'departamento', 'ciudad'])->first();
    }

    public function create(array $data): ConfiguracionEmpresa
    {
        return ConfiguracionEmpresa::create($data)->load(['pais', 'departamento', 'ciudad']);
    }

    public function update(ConfiguracionEmpresa $configuracion, array $data): ConfiguracionEmpresa
    {
        $configuracion->update($data);

        return $configuracion->fresh()->load(['pais', 'departamento', 'ciudad']);
    }
}