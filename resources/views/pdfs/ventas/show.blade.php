<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Venta #{{ $venta->numero_factura }}</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #222;
            margin: 10px;
        }

        .header { margin-bottom: 10px; }
        .section { margin-bottom: 10px; }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 4px;
            padding: 3px 5px;
            background: #f2f2f2;
            border: 1px solid #ddd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 3px 4px;
            vertical-align: top;
        }

        th {
            font-size: 9px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            padding: 4px;
        }

        .detail-table td,
        .totals-table td,
        .payments-table td {
            border: 1px solid #ddd;
            padding: 4px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .small {
            font-size: 9px;
            color: #555;
        }

        .totals-wrapper {
            margin-top: 8px;
            width: 55%;
            margin-left: auto;
        }

        .footer {
            margin-top: 12px;
            font-size: 9px;
            text-align: center;
            color: #666;
        }

        .badge {
            display: inline-block;
            padding: 2px 5px;
            border: 1px solid #ccc;
            font-size: 9px;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td style="width: 20%;">
                    @if($empresa && $empresa->logo_base64)
                        <img
                            src="data:{{ $empresa->logo_mime_type }};base64,{{ $empresa->logo_base64 }}"
                            style="max-width: 90px;"
                        >
                    @endif
                </td>

                <td style="width: 50%;">
                    <div style="font-weight: bold; font-size: 12px;">
                        {{ $empresa?->nombre_comercial ?? $empresa?->nombre_empresa ?? 'Empresa' }}
                    </div>

                    <div class="small">
                        NIT: {{ $empresa?->nit ?? 'N/A' }}{{ $empresa?->dv ? '-' . $empresa->dv : '' }}<br>
                        {{ $empresa?->direccion ?? 'N/A' }}<br>
                        {{ $empresa?->telefono ?? 'N/A' }}<br>
                        {{ $empresa?->email ?? 'N/A' }}
                    </div>
                </td>

                <td style="width: 30%; text-align: right;">
                    <div style="font-weight: bold; font-size: 12px;">
                        COMPROBANTE DE VENTA
                    </div>

                    <div class="small">
                        No: {{ $venta->prefijo ? $venta->prefijo . '-' : '' }}{{ $venta->numero_factura }}<br>
                        Fecha: {{ optional($venta->fecha_venta)->format('Y-m-d H:i') }}<br>
                        Estado: {{ ucfirst($venta->estado) }}<br>
                        Pago: {{ ucfirst($venta->estado_pago) }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Información general</div>
        <table>
            <tr>
                <td>
                    <strong>Cliente:</strong>
                    @if($venta->cliente)
                        {{ $venta->cliente->nombre }} {{ $venta->cliente->apellidos }}
                    @else
                        Consumidor final
                    @endif
                </td>
                <td>
                    <strong>Documento:</strong>
                    {{ $venta->cliente?->documento ?? 'N/A' }}
                </td>
            </tr>
            <tr>
                <td><strong>Vendedor:</strong> {{ $venta->usuario?->name ?? 'N/A' }}</td>
                <td><strong>Tipo venta:</strong> {{ ucfirst($venta->tipo_venta) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Detalle</div>

        <table class="detail-table">
            <thead>
                <tr>
                    <th>Cod</th>
                    <th>Producto</th>
                    <th>UM</th>
                    <th>Cant</th>
                    <th>Precio</th>
                    <th>Desc</th>
                    <th>IVA</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($venta->detalles as $detalle)
                    <tr>
                        <td>{{ $detalle->producto?->codigo ?? 'N/A' }}</td>
                        <td>{{ $detalle->producto?->nombre ?? 'N/A' }}</td>
                        <td>{{ $detalle->producto?->unidadMedida?->abreviatura ?? 'N/A' }}</td>
                        <td class="text-right">{{ number_format((float) $detalle->cantidad, 2, ',', '.') }}</td>
                        <td class="text-right">${{ number_format((float) $detalle->precio_unitario, 0, ',', '.') }}</td>
                        <td class="text-right">${{ number_format((float) $detalle->descuento, 0, ',', '.') }}</td>
                        <td class="text-right">${{ number_format((float) $detalle->iva_valor, 0, ',', '.') }}</td>
                        <td class="text-right">${{ number_format((float) $detalle->total, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No hay productos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="totals-wrapper">
        <table class="totals-table">
            <tr>
                <td><strong>Subtotal</strong></td>
                <td class="text-right">${{ number_format((float) $venta->subtotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Descuento</strong></td>
                <td class="text-right">${{ number_format((float) $venta->descuento, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Impuestos</strong></td>
                <td class="text-right">${{ number_format((float) ($venta->impuesto + $venta->soldicom + $venta->sobre_tasa), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Total</strong></td>
                <td class="text-right">${{ number_format((float) $venta->total, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Total pagado</strong></td>
                <td class="text-right">${{ number_format((float) $venta->total_pagado, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Saldo pendiente</strong></td>
                <td class="text-right">${{ number_format((float) $venta->saldo_pendiente, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    @if($venta->pagos && $venta->pagos->count())
        <div class="section" style="margin-top: 10px;">
            <div class="section-title">Pagos</div>

            <table class="payments-table">
                <thead>
                    <tr>
                        <th>Método</th>
                        <th>Caja</th>
                        <th>Fecha</th>
                        <th class="text-right">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($venta->pagos as $pago)
                        <tr>
                            <td>{{ ucfirst($pago->metodo_pago) }}</td>
                            <td>{{ $pago->caja?->tipo_caja ?? 'N/A' }}</td>
                            <td>{{ optional($pago->fecha_pago)->format('Y-m-d') }}</td>
                            <td class="text-right">${{ number_format((float) $pago->monto, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($venta->observacion)
        <div class="section">
            <div class="section-title">Observación</div>
            <p class="small">{{ $venta->observacion }}</p>
        </div>
    @endif

    @if($venta->estado === 'anulada')
        <div class="section">
            <div class="section-title">Anulación</div>
            <p class="small">
                <strong>Motivo:</strong> {{ $venta->motivo_anulacion ?? 'N/A' }}<br>
                <strong>Usuario:</strong> {{ $venta->usuarioAnulacion?->name ?? 'N/A' }}<br>
                <strong>Fecha:</strong> {{ optional($venta->fecha_anulacion)->format('Y-m-d H:i') ?? 'N/A' }}
            </p>
        </div>
    @endif

    <div class="footer">
        Documento generado automáticamente por el sistema.
    </div>

</body>
</html>