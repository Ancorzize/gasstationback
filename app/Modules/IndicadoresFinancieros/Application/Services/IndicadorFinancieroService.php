<?php

namespace App\Modules\IndicadoresFinancieros\Application\Services;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\IndicadoresFinancieros\Application\Interfaces\IndicadorFinancieroRepositoryInterface;

class IndicadorFinancieroService
{
    public function __construct(
        protected IndicadorFinancieroRepositoryInterface $repository
    ) {}

    public function obtenerIndicador(string $indicador): array
    {
        return match ($indicador) {

            'capital-trabajo' => $this->repository->capitalTrabajo(),

            default => throw new HttpException(
                404,
                'Indicador financiero no encontrado.'
            ),
        };
    }

    public function capitalTrabajo(): array
    {
        return $this->repository->capitalTrabajo();
    }
}