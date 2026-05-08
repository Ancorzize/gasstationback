<?php

namespace App\Modules\Ventas\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetalleVentaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'producto_id' => $this->producto_id,

            'producto' => $this->producto ? [
                'id' => $this->producto->id,
                'codigo' => $this->producto->codigo,
                'nombre' => $this->producto->nombre,
                'marca' => $this->producto?->marca?->nombre,
                'categoria' => $this->producto?->categoriaProducto?->nombre,
                'unidad_medida' => $this->producto?->unidadMedida?->abreviatura,
            ] : null,

            'cantidad' => $this->cantidad,
            'precio_unitario' => $this->precio_unitario,

            'descuento' => $this->descuento,

            'iva' => $this->iva,
            'iva_valor' => $this->iva_valor,

            'soldicom' => $this->soldicom,
            'sobre_tasa' => $this->sobre_tasa,

            'subtotal' => $this->subtotal,
            'total' => $this->total,
        ];
    }
}