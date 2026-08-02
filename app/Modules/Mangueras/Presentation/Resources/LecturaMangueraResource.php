<?php

namespace App\Modules\Mangueras\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LecturaMangueraResource extends JsonResource
{
    public function toArray($request)
    {
        return [

            'id'=>$this->id,

            'fecha'=>$this->turno->fecha_apertura,

            'turno'=>$this->turno->id,

            'islero'=>[
                'id'=>$this->turno->usuario->id,
                'nombre'=>$this->turno->usuario->name,
            ],

            'manguera'=>[
                'id'=>$this->manguera->id,
                'nombre'=>$this->manguera->nombre,
                'codigo'=>$this->manguera->codigo,
            ],

            'bomba'=>[
                'id'=>$this->manguera->bomba->id,
                'nombre'=>$this->manguera->bomba->nombre,
            ],

            'producto'=>[
                'id'=>$this->manguera->producto->id,
                'nombre'=>$this->manguera->producto->nombre,
            ],

            'lectura_inicial'=>(float)$this->lectura_inicial,
            'lectura_final'=>(float)$this->lectura_final,
            'galones_vendidos'=>(float)$this->galones_vendidos,
            'precio_galon'=>(float)$this->precio_galon,
            'total_venta'=>(float)$this->total_venta,

        ];
    }
}