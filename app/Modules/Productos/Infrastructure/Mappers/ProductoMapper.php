<?php

namespace App\Modules\Productos\Infrastructure\Mappers;

use App\Modules\Productos\Application\DTOs\CreateProductoDTO;
use App\Modules\Productos\Application\DTOs\UpdateProductoDTO;

class ProductoMapper
{
    public static function fromArrayToCreateDTO(array $data): CreateProductoDTO
    {
        return new CreateProductoDTO(
            codigo: $data['codigo'],
            nombre: $data['nombre'],
            descripcion: $data['descripcion'] ?? null,
            marca_id: $data['marca_id'] ?? null,
            categoria_producto_id: (int) $data['categoria_producto_id'],
            unidad_medida_id: (int) $data['unidad_medida_id'],
            precio_compra: isset($data['precio_compra']) ? (float) $data['precio_compra'] : null,
            precio_venta: (float) $data['precio_venta'],
            permite_decimal: (bool) $data['permite_decimal'],
            codigo_barras: $data['codigo_barras'] ?? null,
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdateProductoDTO
    {
        return new UpdateProductoDTO(
           codigo: $data['codigo'],
            codigo_barras: $data['codigo_barras'] ?? null,
            nombre: $data['nombre'],
            descripcion: $data['descripcion'] ?? null,
            marca_id: $data['marca_id'] ?? null,
            categoria_producto_id: (int) $data['categoria_producto_id'],
            unidad_medida_id: (int) $data['unidad_medida_id'],
            precio_compra: isset($data['precio_compra'])
                ? (float) $data['precio_compra']
                : null,
            precio_venta: (float) $data['precio_venta'],
            permite_decimal: (bool) $data['permite_decimal'],
                );
    }
}