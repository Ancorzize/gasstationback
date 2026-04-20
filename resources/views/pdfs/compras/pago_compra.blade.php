<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Compra #{{ $data['compra']['id'] }}</title>
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

        table { width: 100%; border-collapse: collapse; }

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
        .totals-table td {
            border: 1px solid #ddd;
            padding: 4px;
        }

        .text-right { text-align: right; }
        .small { font-size: 9px; color: #555; }

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
    </style>
</head>
<body>

    <!-- HEADER -->
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
                        {{ $empresa?->nombre_comercial ?? 'Empresa' }}
                    </div>

                    <div class="small">
                        NIT: {{ $empresa?->nit }}{{ $empresa?->dv ? '-' . $empresa->dv : '' }}<br>
                        {{ $empresa?->direccion }}<br>
                        {{ $empresa?->telefono }}<br>
                        {{ $empresa?->email }}
                    </div>
                </td>

                <td style="width: 30%; text-align: right;">
                    <div style="font-weight: bold; font-size: 12px;">
                        COMPRA #{{ $data['compra']['id'] }}
                    </div>

                    <div class="small">
                        Doc: {{ $data['compra']['numero_documento'] ?: 'N/A' }}<br>
                        Fecha: {{ $data['compra']['fecha_compra'] ?: 'N/A' }}<br>
                        Pago: {{ ucfirst($data['compra']['tipo_pago'] ?? 'N/A') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- INFORMACIÓN GENERAL -->
    <div class="section">
        <div class="section-title">Información general</div>
        <table>
            <tr>
                <td><strong>Proveedor:</strong> {{ $data['proveedor']['nombre'] ?? 'N/A' }}</td>
                <td><strong>NIT proveedor:</strong> {{ $data['proveedor']['nit'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Estado:</strong> {{ ucfirst($data['compra']['estado'] ?? 'N/A') }}</td>
                <td><strong>Pago:</strong> {{ ucfirst($data['compra']['estado_pago'] ?? 'N/A') }}</td>
            </tr>
            <tr>
                <td><strong>Usuario:</strong> {{ $data['usuario']['name'] ?? 'N/A' }}</td>
                <td><strong>Vence:</strong> {{ $data['compra']['fecha_vencimiento'] ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <!-- DETALLE -->
    <div class="section">
        <div class="section-title">Detalle</div>

        <table class="detail-table">
            <thead>
                <tr>
                    <th>Cod</th>
                    <th>Producto</th>
                    <th>UM</th>
                    <th>Cant</th>
                    <th>Costo</th>
                    <th>Subt</th>
                    <th>IVA%</th>
                    <th>IVA</th>
                    <th>Sold</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['detalles'] ?? [] as $detalle)
                    <tr>
                        <td>{{ $detalle['producto']['codigo'] ?? 'N/A' }}</td>
                        <td>{{ $detalle['producto']['nombre'] ?? 'N/A' }}</td>
                        <td>{{ $detalle['producto']['unidad_medida'] ?? 'N/A' }}</td>
                        <td class="text-right">{{ number_format((float) ($detalle['cantidad'] ?? 0), 2, ',', '.') }}</td>
                        <td class="text-right">${{ number_format((float) ($detalle['costo_unitario'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-right">${{ number_format((float) ($detalle['subtotal'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-right">{{ $detalle['iva'] ?? 0 }}</td>
                        <td class="text-right">${{ number_format((float) ($detalle['iva_valor'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-right">${{ number_format((float) ($detalle['soldicom'] ?? 0), 0, ',', '.') }}</td>
                        <td class="text-right">${{ number_format((float) ($detalle['total'] ?? 0), 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align: center;">No hay detalles registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- TOTALES -->
    <div class="totals-wrapper">
        <table class="totals-table">
            <tr>
                <td><strong>Subtotal</strong></td>
                <td class="text-right">${{ number_format((float) ($data['compra']['subtotal'] ?? 0), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Impuestos</strong></td>
                <td class="text-right">
                    ${{ number_format((float) (($data['compra']['impuesto'] ?? 0) + ($data['compra']['soldicom'] ?? 0)), 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td><strong>Total Factura</strong></td>
                <td class="text-right">${{ number_format((float) ($data['compra']['total'] ?? 0), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Monto pagado</strong></td>
                <td class="text-right">${{ number_format((float) ($data['monto'] ?? 0), 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <!-- OBSERVACIÓN -->
    @if(!empty($data['compra']['observacion']))
        <div class="section">
            <div class="section-title">Observación</div>
            <p class="small">{{ $data['compra']['observacion'] }}</p>
        </div>
    @endif

    <div class="footer">
        Documento generado automáticamente
    </div>

</body>
</html>