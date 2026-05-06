<?php

namespace App\Modules\Cartera\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovimientoCarteraResource extends JsonResource
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
            'tipo_movimiento' => $this->tipo_movimiento,
            'origen_modulo' => $this->origen_modulo,
            'origen_id' => $this->origen_id,
            'valor' => $this->valor,
            'saldo_anterior' => $this->saldo_anterior,
            'saldo_nuevo' => $this->saldo_nuevo,
            'medio_pago' => $this->medio_pago,
            'descripcion' => $this->descripcion,
            'user_id' => $this->user_id,
            'usuario' => $this->usuario ? [
                'id' => $this->usuario->id,
                'name' => $this->usuario->name,
                'email' => $this->usuario->email,
            ] : null,
            'fecha_movimiento' => $this->fecha_movimiento,
            'created_at' => $this->created_at,
        ];
    }
}