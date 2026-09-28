<?php

namespace App\Modules\ConfiguracionFacturacion\Application\Interfaces;

use App\Models\ConfiguracionFacturacion;

interface ConfiguracionFacturacionRepositoryInterface
{
    public function first(): ?ConfiguracionFacturacion;

    public function create(array $data): ConfiguracionFacturacion;

    public function update(ConfiguracionFacturacion $configuracion, array $data): ConfiguracionFacturacion;
}
