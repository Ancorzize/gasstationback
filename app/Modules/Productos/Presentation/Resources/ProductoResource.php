<?php

namespace App\Modules\Productos\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,

            'marca_id' => $this->marca_id,
            'marca' => $this->marca ? [
                'id' => $this->marca->id,
                'nombre' => $this->marca->nombre,
            ] : null,

            'categoria_producto_id' => $this->categoria_producto_id,
            'categoria_producto' => $this->categoriaProducto ? [
                'id' => $this->categoriaProducto->id,
                'nombre' => $this->categoriaProducto->nombre,
            ] : null,

            'unidad_medida_id' => $this->unidad_medida_id,
            'unidad_medida' => $this->unidadMedida ? [
                'id' => $this->unidadMedida->id,
                'nombre' => $this->unidadMedida->nombre,
                'abreviatura' => $this->unidadMedida->abreviatura,
            ] : null,

            'precio_compra' => $this->precio_compra,
            'precio_venta' => $this->precio_venta,
            'tipo_impuesto' => $this->tipo_impuesto,
            'porcentaje_iva' => $this->porcentaje_iva !== null ? (float) $this->porcentaje_iva : null,
            'codigo_impuesto' => $this->codigo_impuesto,
            'iva' => $this->porcentaje_iva !== null ? (float) $this->porcentaje_iva : 0.0,
            'clasificacion_tributaria_pendiente' => !$this->tieneClasificacionTributariaValida(),
            'permite_decimal' => $this->permite_decimal,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'stock' => $this->whenLoaded(
                'inventarios',
                function () {

                        $inventario = $this->inventarios->first();

                        return $inventario
                            ? (float) $inventario->cantidad
                            : 0;
                    },
            ),
            'codigo_barras' => $this->codigo_barras,
        ];
    }
}