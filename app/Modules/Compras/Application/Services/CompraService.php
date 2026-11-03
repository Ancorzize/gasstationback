<?php

namespace App\Modules\Compras\Application\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Compra;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Compras\Application\DTOs\CreateCompraDTO;
use App\Modules\Compras\Application\DTOs\UpdateCompraDTO;
use App\Modules\Compras\Application\DTOs\CreatePagoCompraDTO;
use App\Modules\Compras\Application\Interfaces\CompraRepositoryInterface;
use App\Modules\Caja\Application\Interfaces\CajaRepositoryInterface;
use App\Modules\Compras\Application\DTOs\ConfirmarCompraDTO;
use App\Modules\Compras\Application\DTOs\CreatePagoProveedorDTO;
use App\Modules\Compras\Application\DTOs\CreateSaldoInicialProveedorDTO;
use App\Models\Proveedor;
use App\Models\SaldoInicialProveedor;
use App\Models\AplicacionSaldoInicialProveedor;
use App\Models\AnticipoProveedor;
use App\Models\AplicacionAnticipoCompra;
use App\Modules\Compras\Application\DTOs\CreateAnticipoProveedorDTO;
use App\Modules\Compras\Infrastructure\Mappers\CompraMapper;
use App\Modules\Facturacion\Application\DTOs\SolicitudDocumentoSoporteDTO;
use App\Modules\Facturacion\Application\Services\DocumentoSoporteService;

class CompraService
{
    public function __construct(
        protected CompraRepositoryInterface $compraRepository,
        protected CajaRepositoryInterface $cajaRepository,
        protected ?DocumentoSoporteService $documentoSoporteService = null
    ) {}

    public function getAll(array $filters = [])
    {
        return $this->compraRepository->getAll($filters);
    }

    public function findById(int $id): Compra
    {
        $compra = $this->compraRepository->findById($id);

        if (!$compra) {
            throw new HttpException(404, 'Compra no encontrada.');
        }

        return $compra;
    }

    public function create(CreateCompraDTO $dto): Compra
    {
        return DB::transaction(function () use ($dto) {
            $subtotal = collect($dto->detalles)->sum(fn($item) => $item->subtotal());
            $impuesto = $dto->impuesto;
            $soldicom = $dto->soldicom;
            $sobre_tasa = $dto->sobre_tasa;
            $total = $subtotal + $impuesto + $soldicom + $sobre_tasa;

            $estadoDs = $dto->documento_soporte_electronico
                ? ($dto->estado_documento_soporte ?? 'pendiente')
                : 'no_requerido';

            $compra = $this->compraRepository->create([
                'proveedor_id' => $dto->proveedor_id,
                'bodega_id' => $dto->bodega_id,
                'user_id' => $dto->user_id,
                'numero_documento' => $dto->numero_documento,
                'fecha_compra' => $dto->fecha_compra,
                'fecha_vencimiento' => $dto->fecha_vencimiento,
                'tipo_pago' => $dto->tipo_pago,
                'estado' => 'borrador',
                'estado_pago' => 'pendiente',
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'soldicom' => $soldicom,
                'sobre_tasa' => $sobre_tasa,
                'total' => $total,
                'total_pagado' => 0,
                'saldo_pendiente' => $total,
                'observacion' => $dto->observacion,
                'numero_comprobante' => $dto->numero_comprobante,
                'documento_soporte_electronico' => $dto->documento_soporte_electronico,
                'estado_documento_soporte' => $estadoDs,
            ]);

            foreach ($dto->detalles as $detalle) {
                $this->compraRepository->createDetalle([
                    'compra_id' => $compra->id,
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => $detalle->cantidad,
                    'costo_unitario' => $detalle->costo_unitario,
                    'iva' => $detalle->iva,
                    'soldicom' => $detalle->soldicom,
                    'subtotal' => $detalle->subtotal(),
                    'total' => $detalle->total,
                    'iva_valor' => $detalle->iva_valor,
                    'sobre_tasa' => $detalle->sobre_tasa,
                ]);
            }

            return $this->findById($compra->id);
        });
    }

    public function update(int $id, UpdateCompraDTO $dto): Compra
    {
        return DB::transaction(function () use ($id, $dto) {
            $compra = $this->findById($id);

            if ($compra->estado !== 'borrador') {
                throw new HttpException(422, 'Solo se pueden editar compras en borrador.');
            }

            // Si la compra en borrador tiene aplicaciones de anticipo activas, revertirlas automáticamente
            $this->revertirAplicacionesAnticipoCompra(
                $compra,
                (int) (auth()->id() ?? $compra->user_id),
                'Revertida automáticamente por edición de compra en borrador'
            );

            $subtotal = collect($dto->detalles)->sum(fn($item) => $item->subtotal());
            $impuesto = $dto->impuesto;
            $soldicom = $dto->soldicom;
            $sobre_tasa = $dto->sobre_tasa;
            $total = $subtotal + $impuesto + $soldicom + $sobre_tasa;

            $estadoDs = $dto->documento_soporte_electronico
                ? ($dto->estado_documento_soporte ?? 'pendiente')
                : 'no_requerido';

            $this->compraRepository->update($compra, [
                'proveedor_id' => $dto->proveedor_id,
                'bodega_id' => $dto->bodega_id,
                'numero_documento' => $dto->numero_documento,
                'fecha_compra' => $dto->fecha_compra,
                'fecha_vencimiento' => $dto->fecha_vencimiento,
                'tipo_pago' => $dto->tipo_pago,
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'soldicom' => $soldicom,
                'sobre_tasa' => $sobre_tasa,
                'total' => $total,
                'total_pagado' => 0,
                'saldo_pendiente' => $total,
                'observacion' => $dto->observacion,
                'numero_comprobante' => $dto->numero_comprobante,
                'documento_soporte_electronico' => $dto->documento_soporte_electronico,
                'estado_documento_soporte' => $estadoDs,
            ]);

            $this->compraRepository->deleteDetallesByCompra($compra->id);

            foreach ($dto->detalles as $detalle) {
                $this->compraRepository->createDetalle([
                    'compra_id' => $compra->id,
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => $detalle->cantidad,
                    'costo_unitario' => $detalle->costo_unitario,
                    'subtotal' => $detalle->subtotal(),
                    'iva' => $detalle->iva,
                    'soldicom' => $detalle->soldicom,
                    'total' => $detalle->total,
                    'iva_valor' => $detalle->iva_valor,
                    'sobre_tasa' => $detalle->sobre_tasa
                ]);
            }

            return $this->findById($compra->id);
        });
    }

    /**
     * Construir SolicitudDocumentoSoporteDTO a partir de una Compra.
     */
    public function obtenerSolicitudDocumentoSoporte(int $compraId): SolicitudDocumentoSoporteDTO
    {
        $compra = $this->findById($compraId);
        $compra->load(['proveedor', 'detalles.producto']);

        return CompraMapper::toSolicitudDocumentoSoporteDTO($compra);
    }

    public function confirmar(int $id, ConfirmarCompraDTO $dto): Compra
    {
        $compra = DB::transaction(function () use ($id, $dto) {
            $compraPreliminar = Compra::find($id);
            if (!$compraPreliminar) {
                throw new HttpException(404, 'Compra no encontrada.');
            }

            $montoAnticipo = round((float) ($dto->monto_anticipo ?? 0), 2);

            // 1. Jerarquía de bloqueo: Bloquear Proveedor primero si se va a aplicar saldo a favor
            $proveedor = null;
            if ($montoAnticipo > 0) {
                $proveedor = Proveedor::query()
                    ->where('id', $compraPreliminar->proveedor_id)
                    ->lockForUpdate()
                    ->first();

                if (!$proveedor) {
                    throw new HttpException(404, 'Proveedor no encontrado.');
                }
            }

            // 2. Bloquear la compra con lockForUpdate() dentro de la transacción antes de cualquier modificación física o contable
            $compra = Compra::query()
                ->with(['detalles'])
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$compra) {
                throw new HttpException(404, 'Compra no encontrada.');
            }

            // 3. Dentro de la transacción y bajo bloqueo, verificar que compra->estado === 'borrador'
            if ($compra->estado !== 'borrador') {
                throw new HttpException(422, 'Solo se pueden confirmar compras en borrador.');
            }

            $totalCompra = round((float) $compra->total, 2);

            if ($montoAnticipo > $totalCompra) {
                throw new HttpException(422, 'El monto de saldo a favor a aplicar no puede ser mayor al total de la compra.');
            }

            if ($montoAnticipo > 0) {
                if ((int) $compra->proveedor_id !== (int) $proveedor->id) {
                    throw new HttpException(422, 'La compra no corresponde al proveedor bloqueado.');
                }

                $saldoDisponibleTotal = (float) $proveedor->saldo_anticipo;
                if ($montoAnticipo > $saldoDisponibleTotal) {
                    throw new HttpException(422, 'El proveedor no cuenta con suficiente saldo a favor (Disponible: $' . number_format($saldoDisponibleTotal, 2, ',', '.') . ').');
                }
            }

            // 4. Pre-validación de caja (Principio Fail-Fast: antes de modificar inventario, kardex o anticipos)
            $desembolsoCaja = max(0, round($totalCompra - $montoAnticipo, 2));
            $caja = null;

            if ($compra->tipo_pago != 'credito') {
                if ($desembolsoCaja > 0) {
                    if (!$dto->caja_id) {
                        throw new HttpException(
                            422,
                            'Debe seleccionar una caja para compras de contado.'
                        );
                    }
                    $caja = $this->compraRepository
                        ->findCajaById(
                            $dto->caja_id
                        );

                    if (!$caja) {
                        throw new HttpException(
                            422,
                            'La caja seleccionada no existe.'
                        );
                    }

                    if ($caja->estado != 'abierta') {
                        throw new HttpException(
                            422,
                            'La caja se encuentra cerrada.'
                        );
                    }

                    $tipoEsperado =
                        in_array(
                            $compra->tipo_pago,
                            ['efectivo', 'consignacion']
                        )
                        ? 'efectivo'
                        : 'digital';

                    if ($caja->tipo_caja != $tipoEsperado) {
                        throw new HttpException(
                            422,
                            'La caja seleccionada no corresponde al método de pago.'
                        );
                    }

                    $saldoCaja =
                        $this->cajaRepository
                        ->sumMovimientosByTipo(
                            $caja->id,
                            'ingreso'
                        )
                        -
                        $this->cajaRepository
                        ->sumMovimientosByTipo(
                            $caja->id,
                            'egreso'
                        );

                    if ($saldoCaja < $desembolsoCaja) {
                        throw new HttpException(
                            422,
                            'La caja no tiene saldo suficiente para cubrir el desembolso restante ($' . number_format($desembolsoCaja, 2, ',', '.') . ').'
                        );
                    }
                }
            }

            // 5. Modificaciones físicas de inventario y kardex (solo tras validar compras, anticipos y cajas)
            foreach ($compra->detalles as $detalle) {
                $inventario = $this->compraRepository->findInventario(
                    $detalle->producto_id,
                    $compra->bodega_id
                );

                if (!$inventario) {
                    $this->compraRepository->createInventario([
                        'producto_id' => $detalle->producto_id,
                        'bodega_id' => $compra->bodega_id,
                        'cantidad' => 0,
                    ]);
                }

                $this->compraRepository->incrementInventario(
                    $detalle->producto_id,
                    $compra->bodega_id,
                    (float) $detalle->cantidad
                );

                $this->compraRepository->createMovimientoInventario([
                    'tipo_movimiento' => 'entrada_compra',
                    'producto_id' => $detalle->producto_id,
                    'bodega_origen_id' => null,
                    'bodega_destino_id' => $compra->bodega_id,
                    'cantidad' => $detalle->cantidad,
                    'observacion' => 'Entrada por compra #' . $compra->id,
                    'user_id' => $compra->user_id,
                ]);

                $this->compraRepository->updateProductoPrecioCompra(
                    $detalle->producto_id,
                    (float) $detalle->costo_unitario
                );
            }

            // 6. Aplicación de anticipos si corresponde
            if ($montoAnticipo > 0) {
                $anticiposDisponibles = $this->compraRepository
                    ->getAnticiposDisponiblesByProveedor($proveedor->id);

                $restanteAnticipo = $montoAnticipo;
                foreach ($anticiposDisponibles as $ant) {
                    if ($restanteAnticipo <= 0) {
                        break;
                    }

                    $saldoAntAnterior = (float) $ant->saldo_disponible;
                    $aConsumir = min($restanteAnticipo, $saldoAntAnterior);
                    $saldoAntNuevo = round($saldoAntAnterior - $aConsumir, 2);

                    $this->compraRepository->createAplicacionAnticipo([
                        'anticipo_proveedor_id' => $ant->id,
                        'compra_id' => $compra->id,
                        'monto_aplicado' => $aConsumir,
                        'saldo_anterior' => $saldoAntAnterior,
                        'saldo_nuevo' => $saldoAntNuevo,
                        'estado' => 'aplicada',
                        'observacion' => "Aplicación de saldo a favor a Compra #{$compra->id}",
                        'user_id' => $compra->user_id,
                    ]);

                    $this->compraRepository->updateAnticipo($ant, [
                        'saldo_disponible' => $saldoAntNuevo,
                        'estado' => $saldoAntNuevo <= 0 ? 'agotado' : 'disponible',
                    ]);

                    $restanteAnticipo = round($restanteAnticipo - $aConsumir, 2);
                }

                $proveedor->saldo_anticipo = max(0, round((float) $proveedor->saldo_anticipo - $montoAnticipo, 2));
                $proveedor->save();
            }

            // 7. Registro de egreso en caja (si aplica) y actualización de estado
            if ($compra->tipo_pago != 'credito') {
                if ($desembolsoCaja > 0 && $caja) {
                    $this->compraRepository
                        ->createMovimientoCaja([
                            'caja_id' => $caja->id,
                            'tipo_movimiento' => 'egreso',
                            'categoria_movimiento' => 'compra',
                            'origen_modulo' => 'compras',
                            'origen_id' => $compra->id,
                            'medio_pago' => $compra->tipo_pago,
                            'monto' => $desembolsoCaja,
                            'descripcion' => 'Compra #' . $compra->id . ($montoAnticipo > 0 ? " (Pago contado saldo restante)" : ""),
                            'user_id' => $compra->user_id,
                            'fecha_movimiento' => now(),
                        ]);
                }

                $this->compraRepository->update($compra, [
                    'estado' => 'confirmada',
                    'estado_pago' => 'pagado',
                    'total_pagado' => $totalCompra,
                    'saldo_pendiente' => 0,
                ]);
            } else {
                // Compra a crédito
                $estadoPago = $desembolsoCaja <= 0 ? 'pagado' : 'pendiente';
                $this->compraRepository->update($compra, [
                    'estado' => 'confirmada',
                    'estado_pago' => $estadoPago,
                    'total_pagado' => $montoAnticipo,
                    'saldo_pendiente' => $desembolsoCaja,
                ]);
            }

            return $this->findById($compra->id);
        });

        // Automatización de Documento Soporte Electrónico (fuera de la transacción SQL)
        if ((bool) $compra->documento_soporte_electronico) {
            $dsService = $this->documentoSoporteService ?? app(DocumentoSoporteService::class);
            if ($dsService) {
                $dsService->procesarEmisionAutomatica('compra', $compra->id);
                $compra = $this->findById($compra->id);
            }
        }

        return $compra;
    }

    public function getPagos(int $compraId)
    {
        $compra = $this->findById($compraId);

        return $this->compraRepository->getPagosByCompra($compra->id);
    }

    public function getDeudaByProveedor(int $proveedorId): array
    {
        return $this->compraRepository
            ->getDeudaByProveedor($proveedorId);
    }

    public function registrarSaldoInicial(
        CreateSaldoInicialProveedorDTO $dto
    ): SaldoInicialProveedor {
        return DB::transaction(function () use ($dto) {
            $proveedor = Proveedor::query()
                ->where('id', $dto->proveedor_id)
                ->lockForUpdate()
                ->first();

            if (!$proveedor) {
                throw new HttpException(404, 'Proveedor no encontrado.');
            }

            if (!(bool) $proveedor->is_active) {
                throw new HttpException(422, 'El proveedor seleccionado se encuentra inactivo.');
            }

            if ($dto->valor <= 0) {
                throw new HttpException(422, 'El valor del saldo inicial debe ser mayor a cero.');
            }

            if (!empty($dto->numero_documento)) {
                $numeroDocumento = trim((string) $dto->numero_documento);

                if ($this->compraRepository->existeDocumentoProveedor($dto->proveedor_id, $numeroDocumento)) {
                    throw new HttpException(
                        422,
                        "El documento '{$numeroDocumento}' ya se encuentra registrado para este proveedor (como saldo inicial o como compra confirmada)."
                    );
                }
            } else {
                $numeroDocumento = $this->compraRepository->generarSiguienteConsecutivoSIP();
            }

            $monto = round($dto->valor, 2);

            $saldoInicial = $this->compraRepository->createSaldoInicial([
                'proveedor_id' => $proveedor->id,
                'numero_documento' => $numeroDocumento,
                'fecha_documento' => $dto->fecha_documento,
                'valor_original' => $monto,
                'saldo_pendiente' => $monto,
                'estado' => 'pendiente',
                'observacion' => $dto->observacion,
                'user_id' => $dto->user_id,
            ]);

            /*
             * REGLA CONTABLE FASE 1:
             * Aquí NO se crea movimiento de caja (movimientos_caja).
             * El saldo inicial representa una deuda histórica contraída
             * con anterioridad al periodo de operación del ERP.
             */

            return $saldoInicial
                ->fresh()
                ->load(['proveedor', 'usuario']);
        });
    }

    public function getSaldosInicialesByProveedor(int $proveedorId)
    {
        $proveedor = Proveedor::find($proveedorId);
        if (!$proveedor) {
            throw new HttpException(404, 'Proveedor no encontrado.');
        }

        return $this->compraRepository->getSaldosInicialesPendientesByProveedor($proveedorId);
    }

    public function registrarPago(CreatePagoCompraDTO $dto): Compra
    {
        return DB::transaction(function () use ($dto) {

            $compra = $this->findById($dto->compra_id);

            if ($compra->estado !== 'confirmada') {

                throw new HttpException(
                    422,
                    'Solo se pueden registrar pagos a compras confirmadas.'
                );

            }

            if ((float) $compra->saldo_pendiente <= 0) {

                throw new HttpException(
                    422,
                    'La compra ya se encuentra pagada.'
                );

            }

            if ((float) $dto->monto > (float) $compra->saldo_pendiente) {

                throw new HttpException(
                    422,
                    'El monto no puede superar el saldo pendiente.'
                );

            }

            $caja = $this->compraRepository
                ->findCajaById($dto->caja_id);

            if (!$caja) {

                throw new HttpException(
                    422,
                    'La caja seleccionada no existe.'
                );

            }

            if ($caja->estado !== 'abierta') {

                throw new HttpException(
                    422,
                    'La caja seleccionada se encuentra cerrada.'
                );

            }

            $tipoEsperado = in_array(
                $dto->metodo_pago,
                [
                    'efectivo',
                    'consignacion'
                ]
            )
                ? 'efectivo'
                : 'digital';

            if ($caja->tipo_caja !== $tipoEsperado) {

                throw new HttpException(
                    422,
                    'La caja seleccionada no corresponde al método de pago.'
                );

            }

            $saldoCaja =
                $this->cajaRepository
                    ->sumMovimientosByTipo(
                        $caja->id,
                        'ingreso'
                    )
                -
                $this->cajaRepository
                    ->sumMovimientosByTipo(
                        $caja->id,
                        'egreso'
                    );

            if ($dto->monto > $saldoCaja) {

                throw new HttpException(
                    422,
                    'No hay saldo suficiente en la caja.'
                );

            }

            $this->compraRepository->createPago([

                'compra_id' => $compra->id,

                'user_id' => $dto->user_id,

                'fecha_pago' => $dto->fecha_pago,

                'monto' => $dto->monto,

                'metodo_pago' => $dto->metodo_pago,

                'observacion' => $dto->observacion,

            ]);

            $this->compraRepository
                ->createMovimientoCaja([

                    'caja_id' => $caja->id,

                    'tipo_movimiento' => 'egreso',

                    'categoria_movimiento' => 'pago_proveedor',

                    'origen_modulo' => 'compras',

                    'origen_id' => $compra->id,

                    'medio_pago' => $dto->metodo_pago,

                    'monto' => $dto->monto,

                    'descripcion' => "Pago a proveedor por Compra #{$compra->id}",

                    'user_id' => $dto->user_id,

                    'fecha_movimiento' => now(),

                ]);

            $nuevoTotalPagado =
                (float) $compra->total_pagado +
                (float) $dto->monto;

            $nuevoSaldoPendiente =
                (float) $compra->total -
                $nuevoTotalPagado;

            $nuevoEstadoPago =
                $nuevoSaldoPendiente <= 0
                    ? 'pagado'
                    : 'pendiente';

            $this->compraRepository
                ->update($compra, [

                    'total_pagado' => $nuevoTotalPagado,

                    'saldo_pendiente' => $nuevoSaldoPendiente,

                    'estado_pago' => $nuevoEstadoPago,

                ]);

            return $this->findById($compra->id);

        });
    }

    public function registrarPagoProveedor(
        CreatePagoProveedorDTO $dto
    ): array {
        return DB::transaction(function () use ($dto) {

            $proveedor = Proveedor::query()
                ->where('id', $dto->proveedor_id)
                ->lockForUpdate()
                ->first();

            if (!$proveedor) {
                throw new HttpException(
                    404,
                    'Proveedor no encontrado.'
                );
            }

            if (!(bool) $proveedor->is_active) {
                throw new HttpException(
                    422,
                    'El proveedor seleccionado se encuentra inactivo.'
                );
            }

            $montoAbono = round($dto->monto, 2);

            $caja = $this->compraRepository
                ->findCajaById($dto->caja_id);

            if (!$caja) {
                throw new HttpException(
                    422,
                    'La caja seleccionada no existe.'
                );
            }

            if ($caja->estado !== 'abierta') {
                throw new HttpException(
                    422,
                    'La caja seleccionada se encuentra cerrada.'
                );
            }

            $tipoCaja = in_array(
                $dto->metodo_pago,
                ['efectivo', 'consignacion']
            )
                ? 'efectivo'
                : 'digital';

            if ($caja->tipo_caja !== $tipoCaja) {
                throw new HttpException(
                    422,
                    'La caja seleccionada no corresponde al método de pago.'
                );
            }

            $saldoCaja =
                $this->cajaRepository
                    ->sumMovimientosByTipo(
                        $caja->id,
                        'ingreso'
                    )
                -
                $this->cajaRepository
                    ->sumMovimientosByTipo(
                        $caja->id,
                        'egreso'
                    );

            if ($montoAbono > $saldoCaja) {
                throw new HttpException(
                    422,
                    'No hay saldo suficiente en la caja.'
                );
            }

            $compras = $this->compraRepository
                ->getComprasPendientesByProveedor(
                    $dto->proveedor_id
                );

            $saldosIniciales = $this->compraRepository
                ->getSaldosInicialesPendientesByProveedor(
                    $dto->proveedor_id
                );

            $deudas = collect();

            foreach ($saldosIniciales as $si) {
                $deudas->push([
                    'tipo' => 'saldo_inicial',
                    'id' => $si->id,
                    'numero_documento' => $si->numero_documento,
                    'fecha' => $si->fecha_documento?->format('Y-m-d') ?? (string) $si->fecha_documento,
                    'documento' => $si,
                    'saldo_pendiente' => (float) $si->saldo_pendiente,
                    'prioridad' => 1,
                ]);
            }

            foreach ($compras as $compra) {
                $deudas->push([
                    'tipo' => 'compra',
                    'id' => $compra->id,
                    'numero_documento' => $compra->numero_documento ?? ('COMPRA-' . $compra->id),
                    'fecha' => $compra->fecha_compra?->format('Y-m-d') ?? (string) $compra->fecha_compra,
                    'documento' => $compra,
                    'saldo_pendiente' => (float) $compra->saldo_pendiente,
                    'prioridad' => 2,
                ]);
            }

            $deudaTotal = round(
                $deudas->sum(
                    fn ($deuda) =>
                        (float) $deuda['saldo_pendiente']
                ),
                2
            );

            // Orden de aplicación FIFO:
            // 1. Fecha del documento más antigua
            // 2. Si misma fecha, priorizar saldo inicial (prioridad 1 < prioridad 2)
            // 3. Si persiste el empate, ordenar por ID ascendente
            $deudas = $deudas->sort(function ($a, $b) {
                $fA = strtotime((string) $a['fecha']);
                $fB = strtotime((string) $b['fecha']);
                if ($fA !== $fB) {
                    return $fA <=> $fB;
                }
                if ($a['prioridad'] !== $b['prioridad']) {
                    return $a['prioridad'] <=> $b['prioridad'];
                }
                return $a['id'] <=> $b['id'];
            })->values();

            // REGLA FINANCIERA OBLIGATORIA:
            // Un único egreso de caja por el importe total efectivamente entregado.
            $movimientoCaja = $this->compraRepository
                ->createMovimientoCaja([
                    'caja_id' => $caja->id,
                    'tipo_movimiento' => 'egreso',
                    'categoria_movimiento' => 'pago_proveedor',
                    'origen_modulo' => 'proveedores',
                    'origen_id' => $dto->proveedor_id,
                    'medio_pago' => $dto->metodo_pago,
                    'monto' => $montoAbono,
                    'descripcion' => "Abono general a proveedor #{$dto->proveedor_id}",
                    'user_id' => $dto->user_id,
                    'fecha_movimiento' => now(),
                ]);

            $montoRestante = $montoAbono;
            $distribucion = [];

            foreach ($deudas as $deuda) {
                if ($montoRestante <= 0) {
                    break;
                }

                $saldoAnterior = round(
                    (float) $deuda['saldo_pendiente'],
                    2
                );

                $montoAplicado = round(
                    min(
                        $montoRestante,
                        $saldoAnterior
                    ),
                    2
                );

                if ($montoAplicado <= 0) {
                    continue;
                }

                $nuevoSaldoPendiente = round(
                    $saldoAnterior - $montoAplicado,
                    2
                );

                if ($nuevoSaldoPendiente < 0) {
                    $nuevoSaldoPendiente = 0;
                }

                if ($deuda['tipo'] === 'compra') {
                    $compra = $deuda['documento'];

                    $nuevoTotalPagado = round(
                        (float) $compra->total_pagado + $montoAplicado,
                        2
                    );

                    $nuevoEstadoPago =
                        $nuevoSaldoPendiente <= 0
                            ? 'pagado'
                            : 'pendiente';

                    $this->compraRepository->createPago([
                        'compra_id' => $compra->id,
                        'user_id' => $dto->user_id,
                        'fecha_pago' => $dto->fecha_pago,
                        'monto' => $montoAplicado,
                        'metodo_pago' => $dto->metodo_pago,
                        'observacion' => $dto->observacion
                            ?? 'Abono general al proveedor.',
                    ]);

                    $this->compraRepository->update(
                        $compra,
                        [
                            'total_pagado' => $nuevoTotalPagado,
                            'saldo_pendiente' => $nuevoSaldoPendiente,
                            'estado_pago' => $nuevoEstadoPago,
                        ]
                    );

                    $distribucion[] = [
                        'tipo' => 'compra',
                        'compra_id' => $compra->id,
                        'id' => $compra->id,
                        'numero_documento' => $compra->numero_documento ?? ('COMPRA-' . $compra->id),
                        'saldo_anterior' => $saldoAnterior,
                        'monto_aplicado' => $montoAplicado,
                        'saldo_nuevo' => $nuevoSaldoPendiente,
                        'estado_pago' => $nuevoEstadoPago,
                    ];
                }

                if ($deuda['tipo'] === 'saldo_inicial') {
                    $saldoInicial = $deuda['documento'];

                    $nuevoEstado =
                        $nuevoSaldoPendiente <= 0
                            ? 'pagado'
                            : 'parcial';

                    $this->compraRepository->updateSaldoInicial(
                        $saldoInicial,
                        [
                            'saldo_pendiente' => $nuevoSaldoPendiente,
                            'estado' => $nuevoEstado,
                        ]
                    );

                    $this->compraRepository->createAplicacionSaldoInicial([
                        'saldo_inicial_id' => $saldoInicial->id,
                        'movimiento_caja_id' => $movimientoCaja->id,
                        'valor_aplicado' => $montoAplicado,
                        'saldo_anterior' => $saldoAnterior,
                        'saldo_nuevo' => $nuevoSaldoPendiente,
                        'user_id' => $dto->user_id,
                        'fecha_aplicacion' => $dto->fecha_pago,
                        'observacion' => $dto->observacion
                            ?? 'Abono general al proveedor.',
                    ]);

                    $distribucion[] = [
                        'tipo' => 'saldo_inicial',
                        'saldo_inicial_id' => $saldoInicial->id,
                        'id' => $saldoInicial->id,
                        'numero_documento' => $saldoInicial->numero_documento,
                        'saldo_anterior' => $saldoAnterior,
                        'monto_aplicado' => $montoAplicado,
                        'saldo_nuevo' => $nuevoSaldoPendiente,
                        'estado_pago' => $nuevoEstado,
                    ];
                }

                $montoRestante = round(
                    $montoRestante - $montoAplicado,
                    2
                );
            }

            $anticipoGenerado = null;
            if ($montoRestante > 0) {
                $numeroAnticipo = $this->compraRepository->generarSiguienteNumeroAnticipo();
                $origen = $deudas->isEmpty() ? 'directo' : 'pago_excedente';
                $observacionAnticipo = $dto->observacion
                    ?: ($deudas->isEmpty() ? "Anticipo directo al proveedor #{$proveedor->id}." : "Excedente de abono general.");

                $anticipo = $this->compraRepository->createAnticipo([
                    'numero' => $numeroAnticipo,
                    'proveedor_id' => $proveedor->id,
                    'caja_id' => $caja->id,
                    'movimiento_caja_id' => $movimientoCaja->id,
                    'pago_compra_id' => null,
                    'origen' => $origen,
                    'monto_original' => $montoRestante,
                    'saldo_disponible' => $montoRestante,
                    'estado' => 'disponible',
                    'metodo_pago' => $dto->metodo_pago,
                    'referencia' => null,
                    'observacion' => $observacionAnticipo,
                    'user_id' => $dto->user_id,
                ]);

                $proveedor->saldo_anticipo = round((float) $proveedor->saldo_anticipo + $montoRestante, 2);
                $proveedor->save();

                $anticipoGenerado = [
                    'id' => $anticipo->id,
                    'numero' => $anticipo->numero,
                    'monto' => $montoRestante,
                    'origen' => $origen,
                ];
            }

            $totalAplicado = round(
                $montoAbono - $montoRestante,
                2
            );

            return [
                'proveedor_id' => $dto->proveedor_id,
                'monto_abono' => $montoAbono,
                'total_aplicado' => $totalAplicado,
                'saldo_sobrante' => 0,
                'saldo_a_favor_generado' => $montoRestante,
                'anticipo' => $anticipoGenerado,
                'movimiento_caja_id' => $movimientoCaja->id,
                'distribucion' => $distribucion,
            ];
        });
    }

    public function registrarAnticipoDirecto(CreateAnticipoProveedorDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $proveedor = Proveedor::query()
                ->where('id', $dto->proveedor_id)
                ->lockForUpdate()
                ->first();

            if (!$proveedor) {
                throw new HttpException(404, 'Proveedor no encontrado.');
            }

            if (!(bool) $proveedor->is_active) {
                throw new HttpException(422, 'El proveedor seleccionado se encuentra inactivo.');
            }

            $monto = round($dto->monto, 2);
            if ($monto <= 0) {
                throw new HttpException(422, 'El monto del anticipo debe ser mayor a cero.');
            }

            $caja = $this->compraRepository->findCajaById($dto->caja_id);
            if (!$caja) {
                throw new HttpException(422, 'La caja seleccionada no existe.');
            }

            if ($caja->estado !== 'abierta') {
                throw new HttpException(422, 'La caja seleccionada se encuentra cerrada.');
            }

            $tipoCaja = in_array($dto->metodo_pago, ['efectivo', 'consignacion']) ? 'efectivo' : 'digital';
            if ($caja->tipo_caja !== $tipoCaja) {
                throw new HttpException(422, 'La caja seleccionada no corresponde al método de pago.');
            }

            $saldoCaja = $this->cajaRepository->sumMovimientosByTipo($caja->id, 'ingreso')
                - $this->cajaRepository->sumMovimientosByTipo($caja->id, 'egreso');

            if ($monto > $saldoCaja) {
                throw new HttpException(422, 'No hay saldo suficiente en la caja.');
            }

            $movimientoCaja = $this->compraRepository->createMovimientoCaja([
                'caja_id' => $caja->id,
                'tipo_movimiento' => 'egreso',
                'categoria_movimiento' => 'pago_proveedor',
                'origen_modulo' => 'proveedores',
                'origen_id' => $dto->proveedor_id,
                'medio_pago' => $dto->metodo_pago,
                'monto' => $monto,
                'descripcion' => "Anticipo directo a proveedor #{$dto->proveedor_id}",
                'user_id' => $dto->user_id,
                'fecha_movimiento' => now(),
            ]);

            $numeroAnticipo = $this->compraRepository->generarSiguienteNumeroAnticipo();
            $anticipo = $this->compraRepository->createAnticipo([
                'numero' => $numeroAnticipo,
                'proveedor_id' => $proveedor->id,
                'caja_id' => $caja->id,
                'movimiento_caja_id' => $movimientoCaja->id,
                'pago_compra_id' => null,
                'origen' => 'directo',
                'monto_original' => $monto,
                'saldo_disponible' => $monto,
                'estado' => 'disponible',
                'metodo_pago' => $dto->metodo_pago,
                'referencia' => $dto->referencia,
                'observacion' => $dto->observacion ?: "Anticipo directo al proveedor #{$proveedor->id}.",
                'user_id' => $dto->user_id,
            ]);

            $proveedor->saldo_anticipo = round((float) $proveedor->saldo_anticipo + $monto, 2);
            $proveedor->save();

            return [
                'proveedor_id' => $proveedor->id,
                'anticipo_id' => $anticipo->id,
                'numero' => $anticipo->numero,
                'monto' => $monto,
                'saldo_a_favor_total' => (float) $proveedor->saldo_anticipo,
                'movimiento_caja_id' => $movimientoCaja->id,
            ];
        });
    }

    public function aplicarAnticipoCompra(int $compraId, float $monto, int $userId, ?string $observacion = null): array
    {
        return DB::transaction(function () use ($compraId, $monto, $userId, $observacion) {
            $montoAplicar = round($monto, 2);
            if ($montoAplicar <= 0) {
                throw new HttpException(422, 'El monto a aplicar debe ser mayor a cero.');
            }

            // Consulta preliminar para conocer el proveedor asociado
            $compraPreliminar = Compra::find($compraId);
            if (!$compraPreliminar) {
                throw new HttpException(404, 'Compra no encontrada.');
            }

            // 1. Bloqueo de proveedor como primera entidad bloqueada en la transacción
            $proveedor = Proveedor::query()
                ->where('id', $compraPreliminar->proveedor_id)
                ->lockForUpdate()
                ->first();

            if (!$proveedor) {
                throw new HttpException(404, 'Proveedor no encontrado.');
            }

            // 2. Después de bloquear el proveedor, volver a consultar la compra utilizando lockForUpdate()
            $compra = Compra::query()
                ->where('id', $compraId)
                ->lockForUpdate()
                ->first();

            if (!$compra) {
                throw new HttpException(404, 'Compra no encontrada.');
            }

            // 3. Verificar que la compra corresponda al proveedor bloqueado
            if ((int) $compra->proveedor_id !== (int) $proveedor->id) {
                throw new HttpException(422, 'La compra no corresponde al proveedor bloqueado.');
            }

            // 4. Validaciones utilizando los valores actualizados bajo bloqueo
            if ($compra->estado !== 'confirmada') {
                throw new HttpException(422, 'Solo se pueden aplicar anticipos a compras confirmadas.');
            }

            $saldoPendienteActualizado = round((float) $compra->saldo_pendiente, 2);
            if ($saldoPendienteActualizado <= 0) {
                throw new HttpException(422, 'La compra no tiene saldo pendiente por pagar.');
            }

            if ($montoAplicar > $saldoPendienteActualizado) {
                throw new HttpException(422, 'El monto a aplicar no puede ser superior al saldo pendiente de la compra.');
            }

            if ($montoAplicar > (float) $proveedor->saldo_anticipo) {
                throw new HttpException(422, 'El proveedor no dispone de suficiente saldo a favor.');
            }

            // 5. Bloqueo de anticipos disponibles (FIFO)
            $anticipos = $this->compraRepository
                ->getAnticiposDisponiblesByProveedor($proveedor->id);

            $montoRestante = $montoAplicar;
            $aplicaciones = [];

            foreach ($anticipos as $ant) {
                if ($montoRestante <= 0) {
                    break;
                }

                $saldoAntAnterior = (float) $ant->saldo_disponible;
                $aConsumir = min($montoRestante, $saldoAntAnterior);
                $saldoAntNuevo = round($saldoAntAnterior - $aConsumir, 2);

                $app = $this->compraRepository->createAplicacionAnticipo([
                    'anticipo_proveedor_id' => $ant->id,
                    'compra_id' => $compra->id,
                    'monto_aplicado' => $aConsumir,
                    'saldo_anterior' => $saldoAntAnterior,
                    'saldo_nuevo' => $saldoAntNuevo,
                    'estado' => 'aplicada',
                    'observacion' => $observacion ?: "Aplicación de saldo a favor a Compra #{$compra->id}",
                    'user_id' => $userId,
                ]);

                $this->compraRepository->updateAnticipo($ant, [
                    'saldo_disponible' => $saldoAntNuevo,
                    'estado' => $saldoAntNuevo <= 0 ? 'agotado' : 'disponible',
                ]);

                $aplicaciones[] = [
                    'aplicacion_id' => $app->id,
                    'anticipo_id' => $ant->id,
                    'anticipo_numero' => $ant->numero,
                    'monto_aplicado' => $aConsumir,
                ];

                $montoRestante = round($montoRestante - $aConsumir, 2);
            }

            // 6. Actualizar saldo del proveedor
            $proveedor->saldo_anticipo = max(0, round((float) $proveedor->saldo_anticipo - $montoAplicar, 2));
            $proveedor->save();

            // 7. Actualizar compra utilizando los valores actualizados
            $nuevoTotalPagado = round((float) $compra->total_pagado + $montoAplicar, 2);
            $nuevoSaldoPendiente = max(0, round($saldoPendienteActualizado - $montoAplicar, 2));
            $nuevoEstadoPago = $nuevoSaldoPendiente <= 0 ? 'pagado' : 'pendiente';

            $this->compraRepository->update($compra, [
                'total_pagado' => $nuevoTotalPagado,
                'saldo_pendiente' => $nuevoSaldoPendiente,
                'estado_pago' => $nuevoEstadoPago,
            ]);

            return [
                'compra_id' => $compra->id,
                'monto_aplicado' => $montoAplicar,
                'nuevo_saldo_pendiente' => $nuevoSaldoPendiente,
                'estado_pago' => $nuevoEstadoPago,
                'saldo_a_favor_restante' => (float) $proveedor->saldo_anticipo,
                'aplicaciones' => $aplicaciones,
            ];
        });
    }

    public function revertirAplicacionesAnticipoCompra(Compra $compra, int $userId, ?string $motivo = null): array
    {
        return DB::transaction(function () use ($compra, $userId, $motivo) {
            $proveedor = Proveedor::query()
                ->where('id', $compra->proveedor_id)
                ->lockForUpdate()
                ->first();

            $aplicaciones = AplicacionAnticipoCompra::query()
                ->where('compra_id', $compra->id)
                ->where('estado', 'aplicada')
                ->lockForUpdate()
                ->get();

            if ($aplicaciones->isEmpty()) {
                return ['revertidas' => 0, 'monto_total_restituido' => 0];
            }

            $montoTotalRestituido = 0;

            foreach ($aplicaciones as $app) {
                $anticipo = AnticipoProveedor::query()
                    ->where('id', $app->anticipo_proveedor_id)
                    ->lockForUpdate()
                    ->first();

                if ($anticipo) {
                    $nuevoSaldoAnticipo = round((float) $anticipo->saldo_disponible + (float) $app->monto_aplicado, 2);
                    $this->compraRepository->updateAnticipo($anticipo, [
                        'saldo_disponible' => $nuevoSaldoAnticipo,
                        'estado' => 'disponible',
                    ]);
                }

                $this->compraRepository->updateAplicacionAnticipo($app, [
                    'estado' => 'revertida',
                    'revertido_at' => now(),
                    'revertido_by' => $userId,
                    'observacion' => trim(($app->observacion ?? '') . ' | Revertida por anulación: ' . ($motivo ?? 'Sin motivo especificado')),
                ]);

                $montoTotalRestituido += (float) $app->monto_aplicado;
            }

            if ($proveedor && $montoTotalRestituido > 0) {
                $proveedor->saldo_anticipo = round((float) $proveedor->saldo_anticipo + $montoTotalRestituido, 2);
                $proveedor->save();
            }

            return [
                'revertidas' => $aplicaciones->count(),
                'monto_total_restituido' => $montoTotalRestituido,
            ];
        });
    }

    public function getAnticiposByProveedor(int $proveedorId)
    {
        $proveedor = Proveedor::find($proveedorId);
        if (!$proveedor) {
            throw new HttpException(404, 'Proveedor no encontrado.');
        }

        return AnticipoProveedor::query()
            ->with(['usuario', 'caja', 'aplicaciones.compra'])
            ->where('proveedor_id', $proveedorId)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Actualizar el estado de emisión del Documento Soporte Electrónico en la Compra.
     */
    public function actualizarEstadoDocumentoSoporte(int $compraId, string $estado): void
    {
        $compra = $this->findById($compraId);
        $this->compraRepository->update($compra, [
            'estado_documento_soporte' => $estado,
        ]);
    }
}
