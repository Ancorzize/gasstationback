<?php

namespace App\Modules\Gastos\Infrastructure\Mappers;

use App\Models\Gasto;
use App\Modules\Gastos\Application\DTOs\CreateGastoDTO;
use App\Modules\Facturacion\Application\DTOs\SolicitudDocumentoSoporteDTO;

class GastoMapper
{
    public static function fromArrayToCreateDTO(array $data, int $userId): CreateGastoDTO
    {
        $dsActivo = (bool) ($data['documento_soporte_electronico'] ?? false);
        $estadoDs = $dsActivo ? ($data['estado_documento_soporte'] ?? 'pendiente') : 'no_requerido';

        return new CreateGastoDTO(
            fecha_gasto: $data['fecha_gasto'],
            proveedor_id: isset($data['proveedor_id'])
                ? (int) $data['proveedor_id']
                : null,
            categoria_gasto_id: (int) $data['categoria_gasto_id'],
            caja_id: (int) $data['caja_id'],
            tipo_caja: $data['tipo_caja'],
            valor: (float) $data['valor'],
            descripcion: $data['descripcion'],
            user_id: $userId,
            documento_soporte_electronico: $dsActivo,
            estado_documento_soporte: $estadoDs,
        );
    }

    /**
     * Mapear un modelo Gasto hacia el SolicitudDocumentoSoporteDTO desacoplado.
     */
    public static function toSolicitudDocumentoSoporteDTO(Gasto $gasto): SolicitudDocumentoSoporteDTO
    {
        $proveedor = $gasto->proveedor;

        if (!$proveedor) {
            throw new \InvalidArgumentException("No se puede generar Documento Soporte Electrónico para un gasto sin proveedor asociado.");
        }

        $proveedorData = [
            'id' => $proveedor->id,
            'nombre' => $proveedor->nombre,
            'nit' => $proveedor->nit,
            'direccion' => $proveedor->direccion,
            'email' => $proveedor->email,
            'telefono' => $proveedor->telefono,
            'tipo_persona' => $proveedor->tipo_persona,
            'tipo_documento_id' => $proveedor->tipo_documento_id ?? '1',
            'tipo_organization_id' => $proveedor->tipo_organization_id ?? 2,
            'tax_regime_id' => $proveedor->tax_regime_id ?? 2,
            'tax_level_id' => $proveedor->tax_level_id ?? 5,
            'ciudad_id' => $proveedor->ciudad_id ?? '836',
            'pais_id' => $proveedor->pais_id ?? '45',
            'codigo_postal' => $proveedor->codigo_postal ?? '661002',
        ];

        $valorGasto = (float) $gasto->valor;

        $items = [
            [
                'producto_id' => null,
                'codigo' => 'SERV-GASTO',
                'nombre' => $gasto->descripcion ?? 'Gasto operacional',
                'cantidad' => 1.0,
                'precio_unitario' => $valorGasto,
                'subtotal' => $valorGasto,
                'iva_valor' => 0.0,
                'total' => $valorGasto,
            ]
        ];

        $totales = [
            'subtotal' => $valorGasto,
            'impuesto' => 0.0,
            'soldicom' => 0.0,
            'sobre_tasa' => 0.0,
            'total_pagar' => $valorGasto,
        ];

        return new SolicitudDocumentoSoporteDTO(
            gastoId: $gasto->id,
            proveedorId: $gasto->proveedor_id,
            prefijo: null,
            folio: (string) $gasto->id,
            fechaEmision: $gasto->fecha_gasto?->format('Y-m-d'),
            tipoDocumento: 'documento_soporte',
            proveedor: $proveedorData,
            emisor: [],
            items: $items,
            totales: $totales,
            medioPago: $gasto->medio_pago,
            observacion: $gasto->descripcion,
            metadatos: []
        );
    }
}