<?php

namespace App\Modules\Compras\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetalleCompraResource extends JsonResource
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
                'marca' => $this->producto->marca ? [
                    'id' => $this->producto->marca->id,
                    'nombre' => $this->producto->marca->nombre,
                ] : null,
                'categoria_producto' => $this->producto->categoriaProducto ? [
                    'id' => $this->producto->categoriaProducto->id,
                    'nombre' => $this->producto->categoriaProducto->nombre,
                ] : null,
                'unidad_medida' => $this->producto->unidadMedida ? [
                    'id' => $this->producto->unidadMedida->id,
                    'nombre' => $this->producto->unidadMedida->nombre,
                    'abreviatura' => $this->producto->unidadMedida->abreviatura,
                ] : null,
            ] : null,
            'cantidad' => $this->cantidad,
            'costo_unitario' => $this->costo_unitario,
            'subtotal' => $this->subtotal,
        ];
    }
}