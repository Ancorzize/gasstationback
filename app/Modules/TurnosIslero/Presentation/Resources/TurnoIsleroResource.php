<?php

namespace App\Modules\TurnosIslero\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TurnoIsleroResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'estacion_id' => $this->estacion_id,
            'estacion' => $this->estacion ? [
                'id' => $this->estacion->id,
                'nombre' => $this->estacion->nombre,
                'codigo' => $this->estacion->codigo,
            ] : null,

            'user_id' => $this->user_id,
            'usuario' => $this->usuario ? [
                'id' => $this->usuario->id,
                'name' => $this->usuario->name,
                'email' => $this->usuario->email,
            ] : null,

            'fecha_apertura' => $this->fecha_apertura,
            'fecha_cierre' => $this->fecha_cierre,
            'estado' => $this->estado,

            'total_ventas_combustible' => $this->total_ventas_combustible,
            'total_ventas_lubricantes' => $this->total_ventas_lubricantes,
            'total_creditos' => $this->total_creditos,
            'total_abonos' => $this->total_abonos,

            'pagos_qr' => $this->pagos_qr,
            'pagos_datafono' => $this->pagos_datafono,
            'pagos_transferencia' => $this->pagos_transferencia,
            'pagos_consignacion' => $this->pagos_consignacion,
            'pagos_efectivo' => $this->pagos_efectivo,
            'otros_movimientos' => $this->otros_movimientos,
            'otros_movimientos_detalle' => $this->otros_movimientos_detalle,

            'total_reportado' => $this->total_reportado,
            'total_sistema' => $this->total_sistema,
            'balance_final' => $this->balance_final,

            'observacion_apertura' => $this->observacion_apertura,
            'observacion_cierre' => $this->observacion_cierre,

            'lecturas' => LecturaMangueraResource::collection(
                $this->whenLoaded('lecturas')
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}