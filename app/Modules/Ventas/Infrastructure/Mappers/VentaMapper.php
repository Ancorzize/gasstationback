<?php

namespace App\Modules\Ventas\Infrastructure\Mappers;

use App\Modules\Ventas\Application\DTOs\CreateVentaDTO;
use App\Modules\Ventas\Application\DTOs\DetalleVentaDTO;
use App\Modules\Ventas\Application\DTOs\PagoVentaDTO;
use App\Modules\Ventas\Application\DTOs\CreateVentaCombustibleDTO;

class VentaMapper
{
    public static function fromArrayToCreateDTO(array $data, int $userId): CreateVentaDTO
    {
        $detalles = array_map(function ($detalle) {
            return new DetalleVentaDTO(
                producto_id: (int) $detalle['producto_id'],
                cantidad: (float) $detalle['cantidad'],
                precio_unitario: (float) $detalle['precio_unitario'],
                descuento: isset($detalle['descuento']) ? (float) $detalle['descuento'] : 0,
                iva: isset($detalle['iva']) ? (int) $detalle['iva'] : 0,
                iva_valor: isset($detalle['iva_valor']) ? (float) $detalle['iva_valor'] : 0,
                soldicom: isset($detalle['soldicom']) ? (float) $detalle['soldicom'] : 0,
                sobre_tasa: isset($detalle['sobre_tasa']) ? (float) $detalle['sobre_tasa'] : 0,
                total: (float) $detalle['total'],
            );
        }, $data['detalles']);

        $pagos = array_map(function ($pago) {
            return new PagoVentaDTO(
                metodo_pago: $pago['metodo_pago'],
                monto: (float) $pago['monto'],
                observacion: $pago['observacion'] ?? null,
            );
        }, $data['pagos']);

        return new CreateVentaDTO(
            cliente_id: isset($data['cliente_id']) ? (int) $data['cliente_id'] : null,
            user_id: $userId,
            tipo_venta: $data['tipo_venta'],
            observacion: $data['observacion'] ?? null,
            detalles: $detalles,
            pagos: $pagos,
        );
    }

    public static function fromArrayToCreateCombustibleDTO(array $data, int $userId): CreateVentaCombustibleDTO
    {
        return new CreateVentaCombustibleDTO(
            manguera_id: (int) $data['manguera_id'],
            tipo_venta: $data['tipo_venta'],
            cliente_id: isset($data['cliente_id']) ? (int) $data['cliente_id'] : null,
            metodo_pago: $data['metodo_pago'],
            monto: (float) $data['monto'],
            observacion: $data['observacion'] ?? null,
            user_id: $userId,
        );
    }
}