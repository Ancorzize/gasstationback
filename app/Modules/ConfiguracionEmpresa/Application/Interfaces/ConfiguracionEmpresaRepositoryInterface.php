<?php

namespace App\Modules\ConfiguracionEmpresa\Application\Interfaces;

use App\Models\ConfiguracionEmpresa;

interface ConfiguracionEmpresaRepositoryInterface
{
    public function first(): ?ConfiguracionEmpresa;

    public function create(array $data): ConfiguracionEmpresa;

    public function update(ConfiguracionEmpresa $configuracion, array $data): ConfiguracionEmpresa;
}