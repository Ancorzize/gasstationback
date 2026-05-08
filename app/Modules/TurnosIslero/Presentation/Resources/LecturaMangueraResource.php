<?php

namespace App\Modules\TurnosIslero\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LecturaMangueraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'turno_islero_id' => $this->turno_islero_id,
            'manguera_id' => $this->manguera_id,

            'manguera' => $this->manguera ? [
                'id' => $this->manguera->id,
                'nombre' => $this->manguera->nombre,
                'codigo' => $this->manguera->codigo,
                'precio_actual' => $this->manguera->precio_actual,
                'bomba' => $this->manguera->bomba ? [
                    'id' => $this->manguera->bomba->id,
                    'nombre' => $this->manguera->bomba->nombre,
                    'codigo' => $this->manguera->bomba->codigo,
                ] : null,
                'producto' => $this->manguera->producto ? [
                    'id' => $this->manguera->producto->id,
                    'codigo' => $this->manguera->producto->codigo,
                    'nombre' => $this->manguera->producto->nombre,
                ] : null,
            ] : null,

            'lectura_inicial' => $this->lectura_inicial,
            'lectura_final' => $this->lectura_final,
            'galones_vendidos' => $this->galones_vendidos,
            'precio_galon' => $this->precio_galon,
            'total_venta' => $this->total_venta,
        ];
    }
}