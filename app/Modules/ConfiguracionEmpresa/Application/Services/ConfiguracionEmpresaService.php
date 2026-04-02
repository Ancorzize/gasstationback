<?php

namespace App\Modules\ConfiguracionEmpresa\Application\Services;

use App\Models\Ciudad;
use App\Models\Departamento;
use App\Models\ConfiguracionEmpresa;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\ConfiguracionEmpresa\Application\DTOs\UpdateConfiguracionEmpresaDTO;
use App\Modules\ConfiguracionEmpresa\Application\Interfaces\ConfiguracionEmpresaRepositoryInterface;

class ConfiguracionEmpresaService
{
    public function __construct(
        protected ConfiguracionEmpresaRepositoryInterface $repository
    ) {}

    public function get(): ?ConfiguracionEmpresa
    {
        return $this->repository->first();
    }

    public function update(UpdateConfiguracionEmpresaDTO $dto): ConfiguracionEmpresa
    {
        $this->validarJerarquiaUbicacion(
            $dto->pais_id,
            $dto->departamento_id,
            $dto->ciudad_id
        );

        $data = [
            'nombre_empresa' => $dto->nombre_empresa,
            'nombre_comercial' => $dto->nombre_comercial,
            'nit' => $dto->nit,
            'dv' => $dto->dv,
            'email' => $dto->email,
            'telefono' => $dto->telefono,
            'direccion' => $dto->direccion,
            'pais_id' => $dto->pais_id,
            'departamento_id' => $dto->departamento_id,
            'ciudad_id' => $dto->ciudad_id,
            'responsable_iva' => $dto->responsable_iva,
            'regimen' => $dto->regimen,
            'porcentaje_iva' => $dto->porcentaje_iva,
            'maneja_iva_incluido' => $dto->maneja_iva_incluido,
            'prefijo_factura' => $dto->prefijo_factura,
            'numero_resolucion' => $dto->numero_resolucion,
            'fecha_resolucion' => $dto->fecha_resolucion,
            'rango_desde' => $dto->rango_desde,
            'rango_hasta' => $dto->rango_hasta,
            'fecha_vencimiento' => $dto->fecha_vencimiento,
            'moneda' => $dto->moneda,
            'simbolo_moneda' => $dto->simbolo_moneda,
            'decimales' => $dto->decimales,
        ];

        if (request()->hasFile('logo')) {
            $file = request()->file('logo');

            $data['logo_base64'] = base64_encode(
                file_get_contents($file->getRealPath())
            );

            $data['logo_mime_type'] = $file->getMimeType();
        }

        $configuracion = $this->repository->first();

        if (!$configuracion) {
            return $this->repository->create($data);
        }

        return $this->repository->update($configuracion, $data);
    }

    private function validarJerarquiaUbicacion(?int $paisId, ?int $departamentoId, ?int $ciudadId): void
    {
        if ($departamentoId) {
            $departamento = Departamento::find($departamentoId);

            if (!$departamento) {
                throw new HttpException(422, 'El departamento seleccionado no existe.');
            }

            if ($paisId && $departamento->pais_id !== $paisId) {
                throw new HttpException(422, 'El departamento no pertenece al país seleccionado.');
            }
        }

        if ($ciudadId) {
            $ciudad = Ciudad::find($ciudadId);

            if (!$ciudad) {
                throw new HttpException(422, 'La ciudad seleccionada no existe.');
            }

            if ($departamentoId && $ciudad->departamento_id !== $departamentoId) {
                throw new HttpException(422, 'La ciudad no pertenece al departamento seleccionado.');
            }
        }

        if ($ciudadId && !$departamentoId) {
            throw new HttpException(422, 'Debe seleccionar un departamento para la ciudad indicada.');
        }

        if ($departamentoId && !$paisId) {
            throw new HttpException(422, 'Debe seleccionar un país para el departamento indicado.');
        }
    }
}