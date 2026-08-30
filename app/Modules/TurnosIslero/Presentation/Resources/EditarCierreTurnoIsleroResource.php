<?php

namespace App\Modules\TurnosIslero\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EditarCierreTurnoIsleroResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $datosCierre = $this->datos_cierre_pendiente ?? [];

        return [

            'id' => $this->id,

            'estado' => $this->estado,

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

            /*
             * Motivo por el cual el administrador
             * devolvió el cierre.
             */
            'observacion_devolucion' =>
                $this->observacion_devolucion,

            /*
             * Datos que el islero había enviado
             * anteriormente al solicitar el cierre.
             */
            'datos_cierre_pendiente' => [

                'lecturas_finales' =>
                    $datosCierre['lecturas_finales'] ?? [],

                'destinos_recaudo' =>
                    $datosCierre['destinos_recaudo'] ?? [],

                'otros_movimientos' =>
                    $datosCierre['otros_movimientos'] ?? 0,

                'otros_movimientos_detalle' =>
                    $datosCierre['otros_movimientos_detalle'] ?? null,

                'observacion_cierre' =>
                    $datosCierre['observacion_cierre'] ?? null,
                
                'total_ventas_lubricantes' => 
                    $datosCierre['total_ventas_lubricantes'] ?? null,

                'total_ventas_combustible' => 
                    $datosCierre['total_ventas_combustible'] ?? null,

                'total_abonos' => 
                    $datosCierre['total_abonos'] ?? null,

                'total_dinero_recaudado' => 
                    $datosCierre['total_dinero_recaudado'] ?? null,
            ],  

            /*
             * Información actual de las lecturas
             * del turno.
             */
            'lecturas' => LecturaMangueraResource::collection(
                $this->whenLoaded('lecturas')
            ),
        ];
    }
}