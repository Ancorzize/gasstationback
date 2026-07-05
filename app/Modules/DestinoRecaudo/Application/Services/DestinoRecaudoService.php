<?php

namespace App\Modules\DestinoRecaudo\Application\Services;

use App\Models\DestinoRecaudo;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\DestinoRecaudo\Application\DTOs\CreateDestinoRecaudoDTO;
use App\Modules\DestinoRecaudo\Application\DTOs\UpdateDestinoRecaudoDTO;
use App\Modules\DestinoRecaudo\Application\Interfaces\DestinoRecaudoRepositoryInterface;

class DestinoRecaudoService
{
    public function __construct(
        protected DestinoRecaudoRepositoryInterface $repository
    ) {}

    public function paginate(array $filters=[],int $perPage=10)
    {
        return $this->repository
            ->paginate($filters,$perPage);
    }

    public function findById(int $id):DestinoRecaudo
    {
        $destino=$this->repository->findById($id);

        if(!$destino){
            throw new HttpException(
                404,
                'Destino de recaudo no encontrado.'
            );
        }

        return $destino;
    }

    public function create(CreateDestinoRecaudoDTO $dto):DestinoRecaudo
    {
        return $this->repository->create([

            'codigo'=>$dto->codigo,

            'nombre'=>$dto->nombre,

            'descripcion'=>$dto->descripcion,

            'is_active'=>true,

        ]);
    }

    public function update(
        int $id,
        UpdateDestinoRecaudoDTO $dto
    ):DestinoRecaudo{

        $destino=$this->findById($id);

        return $this->repository->update(
            $destino,
            [

                'codigo'=>$dto->codigo,

                'nombre'=>$dto->nombre,

                'descripcion'=>$dto->descripcion,

            ]
        );
    }

    public function changeStatus(
        int $id,
        bool $status
    ):DestinoRecaudo{

        return $this->repository
            ->changeStatus(
                $this->findById($id),
                $status
            );

    }

    public function delete(int $id):void
    {
        $this->repository
            ->delete(
                $this->findById($id)
            );
    }
}