<?php

namespace App\Modules\Cartera\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbonoCarteraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cliente_id' => $this->cliente_id,
            'cliente' => $this->cliente ? [
                'id' => $this->cliente->id,
                'nombre' => $this->cliente->nombre,
                'apellidos' => $this->cliente->apellidos,
                'documento' => $this->cliente->documento,
            ] : null,
            'caja_id' => $this->caja_id,
            'caja' => $this->caja ? [
                'id' => $this->caja->id,
                'tipo_caja' => $this->caja->tipo_caja,
                'estado' => $this->caja->estado,
            ] : null,
            'fecha_abono' => $this->fecha_abono?->format('Y-m-d'),
            'valor' => $this->valor,
            'medio_pago' => $this->medio_pago,
            'observacion' => $this->observacion,
            'estado' => $this->estado,
            'user_id' => $this->user_id,
            'usuario' => $this->usuario ? [
                'id' => $this->usuario->id,
                'name' => $this->usuario->name,
                'email' => $this->usuario->email,
            ] : null,
            'created_at' => $this->created_at,
            'turno_islero_id' => $this->turno_islero_id,
            'turno_islero' => $this->turnoIslero ? [
                'id' => $this->turnoIslero->id,
                'estado' => $this->turnoIslero->estado,
                'fecha_apertura' => $this->turnoIslero->fecha_apertura,
                'fecha_cierre' => $this->turnoIslero->fecha_cierre,
                'estacion' => $this->turnoIslero->estacion ? [
                    'id' => $this->turnoIslero->estacion->id,
                    'nombre' => $this->turnoIslero->estacion->nombre,
                    'codigo' => $this->turnoIslero->estacion->codigo,
                ] : null,
            ] : null,
        ];
    }
}