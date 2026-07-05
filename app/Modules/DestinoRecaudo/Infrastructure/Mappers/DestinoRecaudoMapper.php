<?php

namespace App\Modules\DestinoRecaudo\Infrastructure\Mappers;

use App\Modules\DestinoRecaudo\Application\DTOs\CreateDestinoRecaudoDTO;
use App\Modules\DestinoRecaudo\Application\DTOs\UpdateDestinoRecaudoDTO;

class DestinoRecaudoMapper
{
    public static function fromArrayToCreateDTO(array $data):CreateDestinoRecaudoDTO
    {
        return new CreateDestinoRecaudoDTO(

            codigo:$data['codigo'],

            nombre:$data['nombre'],

            descripcion:$data['descripcion']??null

        );
    }

    public static function fromArrayToUpdateDTO(array $data):UpdateDestinoRecaudoDTO
    {
        return new UpdateDestinoRecaudoDTO(

            codigo:$data['codigo'],

            nombre:$data['nombre'],

            descripcion:$data['descripcion']??null

        );
    }
}