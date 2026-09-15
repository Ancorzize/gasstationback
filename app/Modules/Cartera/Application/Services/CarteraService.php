<?php

namespace App\Modules\Cartera\Application\Services;

use App\Models\Cliente;
use App\Models\AbonoCartera;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Cartera\Application\DTOs\CreateAbonoCarteraDTO;
use App\Modules\Cartera\Application\Interfaces\CarteraRepositoryInterface;
use App\Modules\Cartera\Application\DTOs\CreateAbonoCarteraDetalleDTO;
use App\Modules\Cartera\Application\DTOs\CreateSaldoInicialCarteraDTO;
use App\Models\SaldoInicialCartera;
use App\Modules\TurnosIslero\Application\Interfaces\TurnoIsleroRepositoryInterface;

class CarteraService
{
    public function __construct(
        protected CarteraRepositoryInterface $carteraRepository,
        protected TurnoIsleroRepositoryInterface $turnoRepository
    ) {}

    public function resumen(): array
    {
        return $this->carteraRepository->resumen();
    }

    public function paginateMovimientos(array $filters = [], int $perPage = 10)
    {
        return $this->carteraRepository->paginateMovimientos($filters, $perPage);
    }

    public function estadoCuenta(int $clienteId): array
    {
        $cliente = $this->carteraRepository->findClienteById($clienteId);

        if (!$cliente) {
            throw new HttpException(404, 'Cliente no encontrado.');
        }

        $movimientos = $this->carteraRepository->getMovimientosByCliente($cliente->id);

        return [
            'cliente' => $cliente,
            'cupo_credito' => (float) $cliente->cupo_credito,
            'saldo_credito' => (float) $cliente->saldo_credito,
            'cupo_disponible' => (float) $cliente->cupo_credito - (float) $cliente->saldo_credito,
            'movimientos' => $movimientos,
        ];
    }

    public function configurarCredito(
        int $clienteId,
        bool $manejaCredito,
        float $cupoCredito,
        ?int $diasCredito
    ): Cliente {
        $cliente = $this->carteraRepository->findClienteById($clienteId);

        if (!$cliente) {
            throw new HttpException(404, 'Cliente no encontrado.');
        }

        if ($cupoCredito < (float) $cliente->saldo_credito) {
            throw new HttpException(
                422,
                'El cupo de crédito no puede ser menor al saldo actual del cliente.'
            );
        }

        return $this->carteraRepository->updateCliente($cliente, [
            'maneja_credito' => $manejaCredito,
            'cupo_credito' => $manejaCredito ? $cupoCredito : 0,
            'dias_credito' => $manejaCredito ? $diasCredito : null,
        ]);
    }

    public function registrarAbono(CreateAbonoCarteraDTO $dto): AbonoCartera
    {
        return DB::transaction(function () use ($dto) {

            $cliente = $this->carteraRepository
                ->findClienteById($dto->cliente_id);

            if (!$cliente) {
                throw new HttpException(
                    404,
                    'Cliente no encontrado.'
                );
            }

            if (!(bool) $cliente->maneja_credito) {
                throw new HttpException(
                    422,
                    'El cliente no tiene crédito habilitado.'
                );
            }

            if ((float) $cliente->saldo_credito <= 0) {
                throw new HttpException(
                    422,
                    'El cliente no tiene saldo pendiente.'
                );
            }

            if ($dto->valor <= 0) {
                throw new HttpException(
                    422,
                    'El valor del abono debe ser mayor a cero.'
                );
            }

            if ($dto->valor > (float) $cliente->saldo_credito) {
                throw new HttpException(
                    422,
                    'El abono no puede superar el saldo pendiente.'
                );
            }

            $caja = $this->carteraRepository
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
                    'La caja seleccionada no se encuentra abierta.'
                );
            }

            $tipoCaja = in_array(
                $dto->medio_pago,
                ['efectivo', 'consignacion']
            )
                ? 'efectivo'
                : 'digital';

            if ($caja->tipo_caja !== $tipoCaja) {
                throw new HttpException(
                    422,
                    'La caja seleccionada no corresponde al medio de pago.'
                );
            }

            $turnoAbierto = $this->carteraRepository
                ->getTurnoAbiertoByUser($dto->user_id);

            $ventasPendientes = $this->carteraRepository
                ->getVentasPendientesCliente($cliente->id);

            $saldosInicialesPendientes = $this->carteraRepository
                ->getSaldosInicialesPendientesCliente($cliente->id);

            $deudas = collect();

            foreach ($ventasPendientes as $venta) {

                $deudas->push([
                    'tipo' => 'venta',
                    'id' => $venta->id,
                    'fecha' => $venta->fecha_venta,
                    'documento' => $venta,
                    'saldo_pendiente' => (float) $venta->saldo_pendiente,
                ]);
            }

            foreach ($saldosInicialesPendientes as $saldoInicial) {

                $deudas->push([
                    'tipo' => 'saldo_inicial',
                    'id' => $saldoInicial->id,
                    'fecha' => $saldoInicial->fecha_documento,
                    'documento' => $saldoInicial,
                    'saldo_pendiente' => (float) $saldoInicial->saldo_pendiente,
                ]);
            }

            if ($deudas->isEmpty()) {
                throw new HttpException(
                    422,
                    'El cliente no tiene documentos pendientes de pago.'
                );
            }

            $deudas = $deudas
                ->sortBy(function ($deuda) {

                    $fecha = $deuda['fecha'];

                    if ($fecha instanceof \Carbon\CarbonInterface) {
                        return $fecha->timestamp;
                    }

                    return strtotime((string) $fecha);
                })
                ->values();

            if ($turnoAbierto) {
                $abono = $this->carteraRepository->createAbono([
                    'cliente_id' => $cliente->id,
                    'caja_id' => $caja->id,
                    'fecha_abono' => $dto->fecha_abono,
                    'valor' => $dto->valor,
                    'medio_pago' => $dto->medio_pago,
                    'observacion' => $dto->observacion,
                    'estado' => 'pendiente',
                    'user_id' => $dto->user_id,
                    'turno_islero_id' => $turnoAbierto->id,
                ]);

                $totalesTurno = $this->turnoRepository->recalcularTotalesTurno($turnoAbierto->id);
                $turnoAbierto->update($totalesTurno);

                return $abono
                    ->fresh()
                    ->load([
                        'cliente',
                        'caja',
                        'usuario',
                        'detalles.venta',
                    ]);
            }

            $saldoAnterior = (float) $cliente->saldo_credito;

            $abono = $this->carteraRepository->createAbono([
                'cliente_id' => $cliente->id,
                'caja_id' => $caja->id,
                'fecha_abono' => $dto->fecha_abono,
                'valor' => $dto->valor,
                'medio_pago' => $dto->medio_pago,
                'observacion' => $dto->observacion,
                'estado' => 'aplicado',
                'user_id' => $dto->user_id,
                'turno_islero_id' => null,
                'fecha_aplicacion' => now(),
                'user_aplicacion_id' => $dto->user_id,
            ]);

            $valorPendienteAbono = (float) $dto->valor;

            foreach ($deudas as $deuda) {

                if ($valorPendienteAbono <= 0) {
                    break;
                }

                $saldoDeuda = (float) $deuda['saldo_pendiente'];

                if ($saldoDeuda <= 0) {
                    continue;
                }

                $valorAplicado = min(
                    $saldoDeuda,
                    $valorPendienteAbono
                );

                $nuevoSaldo = $saldoDeuda - $valorAplicado;

                if ($nuevoSaldo < 0) {
                    $nuevoSaldo = 0;
                }


                if ($deuda['tipo'] === 'venta') {

                    $venta = $deuda['documento'];


                    $estadoPago = $nuevoSaldo <= 0
                        ? 'pagado'
                        : 'parcial';


                    $this->carteraRepository->updateVenta(
                        $venta,
                        [
                            'saldo_pendiente' => $nuevoSaldo,
                            'estado_pago' => $estadoPago,
                        ]
                    );

                    $this->carteraRepository->createAbonoDetalle(

                        new CreateAbonoCarteraDetalleDTO(
                            abono_cartera_id: $abono->id,
                            venta_id: $venta->id,
                            valor_aplicado: $valorAplicado,

                        )
                    );
                }

                if ($deuda['tipo'] === 'saldo_inicial') {

                    $saldoInicial = $deuda['documento'];
                    $estado = $nuevoSaldo <= 0
                        ? 'pagado'
                        : 'parcial';

                    $this->carteraRepository->updateSaldoInicial(
                        $saldoInicial,
                        [
                            'saldo_pendiente' => $nuevoSaldo,
                            'estado' => $estado,
                        ]
                    );

                    $this->carteraRepository
                        ->createAplicacionSaldoInicial([
                            'abono_cartera_id' => $abono->id,
                            'saldo_inicial_id' => $saldoInicial->id,
                            'valor_aplicado' => $valorAplicado,
                        ]);
                }

                $valorPendienteAbono -= $valorAplicado;
            }

            if ($valorPendienteAbono > 0.001) {

                throw new HttpException(
                    500,
                    'No fue posible aplicar completamente el abono a las deudas pendientes.'
                );
            }

            $saldoVentas = $this->carteraRepository
                ->getVentasPendientesCliente($cliente->id)
                ->sum('saldo_pendiente');


            $saldoIniciales = $this->carteraRepository
                ->getSaldosInicialesPendientesCliente($cliente->id)
                ->sum('saldo_pendiente');


            $saldoNuevo = (float) $saldoVentas
                + (float) $saldoIniciales;

            $this->carteraRepository->updateCliente(
                $cliente,
                [
                    'saldo_credito' => $saldoNuevo,
                ]
            );

            $this->carteraRepository->createMovimientoCartera([

                'cliente_id' => $cliente->id,

                'tipo_movimiento' => 'abono',

                'origen_modulo' => 'abonos_cartera',

                'origen_id' => $abono->id,

                'valor' => $dto->valor,

                'saldo_anterior' => $saldoAnterior,

                'saldo_nuevo' => $saldoNuevo,

                'medio_pago' => $dto->medio_pago,

                'descripcion' => $dto->observacion
                    ?: 'Abono aplicado automáticamente a las deudas pendientes.',

                'user_id' => $dto->user_id,

                'fecha_movimiento' => now(),

            ]);

            if (!$turnoAbierto) {
                $this->carteraRepository->createMovimientoCaja([
                    'caja_id' => $caja->id,
                    'tipo_movimiento' => 'ingreso',
                    'categoria_movimiento' => 'abono_cartera',
                    'origen_modulo' => 'cartera',
                    'origen_id' => $abono->id,
                    'medio_pago' => $dto->medio_pago,
                    'monto' => $dto->valor,
                    'descripcion' => 'Abono cartera cliente #' . $cliente->id,
                    'user_id' => $dto->user_id,
                    'fecha_movimiento' => now(),
                ]);
            }

            return $abono
                ->fresh()
                ->load([
                    'cliente',
                    'caja',
                    'usuario',
                    'detalles.venta',
                ]);
        });
    }

    public function registrarSaldoInicial(
        CreateSaldoInicialCarteraDTO $dto
    ): SaldoInicialCartera
    {
        return DB::transaction(function () use ($dto) {

            $cliente = $this->carteraRepository
                ->findClienteById($dto->cliente_id);

            if (!$cliente) {
                throw new HttpException(
                    404,
                    'Cliente no encontrado.'
                );
            }

            if (!(bool) $cliente->maneja_credito) {
                throw new HttpException(
                    422,
                    'El cliente no tiene crédito habilitado.'
                );
            }

            if ($dto->valor <= 0) {
                throw new HttpException(
                    422,
                    'El valor del saldo inicial debe ser mayor a cero.'
                );
            }

            $saldoInicial = $this->carteraRepository
                ->createSaldoInicial([
                    'cliente_id' => $cliente->id,
                    'fecha_documento' => $dto->fecha_documento,
                    'valor_original' => $dto->valor,
                    'saldo_pendiente' => $dto->valor,
                    'estado' => 'pendiente',
                    'observacion' => $dto->observacion,
                    'user_id' => $dto->user_id,
                ]);

            $saldoAnterior = (float) $cliente->saldo_credito;

            $saldoNuevo = $saldoAnterior + $dto->valor;

            $this->carteraRepository->updateCliente(
                $cliente,
                [
                    'saldo_credito' => $saldoNuevo,
                ]
            );

            $this->carteraRepository->createMovimientoCartera([
                'cliente_id' => $cliente->id,
                'tipo_movimiento' => 'saldo_inicial',
                'origen_modulo' => 'cartera',
                'origen_id' => $saldoInicial->id,
                'valor' => $dto->valor,
                'saldo_anterior' => $saldoAnterior,
                'saldo_nuevo' => $saldoNuevo,
                'medio_pago' => null,
                'descripcion' => $dto->observacion
                    ?: 'Registro de saldo inicial de cartera.',
                'user_id' => $dto->user_id,
                'fecha_movimiento' => now(),
            ]);

            /*
            * IMPORTANTE:
            *
            * Aquí NO se crea movimiento de caja.
            *
            * El saldo inicial representa una deuda que
            * ya existía antes de utilizar el sistema.
            */

            return $saldoInicial
                ->fresh()
                ->load([
                    'cliente',
                    'usuario',
                ]);
        });
    }

    public function anularAbono(int $id, string $motivo, int $userId): AbonoCartera
    {
        return DB::transaction(function () use ($id, $motivo, $userId) {
            $abono = AbonoCartera::with(['detalles.venta', 'aplicacionesSaldoInicial.saldoInicial', 'turnoIslero'])
                ->find($id);

            if (!$abono) {
                throw new HttpException(404, 'Abono de cartera no encontrado.');
            }

            if ($abono->estado === 'anulado') {
                throw new HttpException(422, 'El abono ya se encuentra anulado.');
            }

            if ($abono->turno_islero_id) {
                $turno = $abono->turnoIslero;
                if ($turno && $turno->estado === 'cerrado') {
                    throw new HttpException(
                        422,
                        'No se puede anular el abono porque el turno de islero donde fue registrado ya se encuentra cerrado.'
                    );
                }
            }

            if ($abono->estado === 'pendiente') {
                $abono->update([
                    'estado' => 'anulado',
                    'observacion' => trim($abono->observacion . " [ANULADO: {$motivo}]"),
                ]);

                if ($abono->turno_islero_id) {
                    $totalesTurno = $this->turnoRepository->recalcularTotalesTurno($abono->turno_islero_id);
                    $abono->turnoIslero?->update($totalesTurno);
                }

                return $abono->fresh(['cliente', 'caja', 'usuario', 'turnoIslero']);
            }

            foreach ($abono->detalles as $detalle) {
                $venta = $detalle->venta;
                if ($venta) {
                    $nuevoSaldo = round((float) $venta->saldo_pendiente + (float) $detalle->valor_aplicado, 2);
                    $totalVenta = (float) $venta->total;
                    $estadoPago = $nuevoSaldo >= $totalVenta ? 'pendiente' : ($nuevoSaldo > 0 ? 'parcial' : 'pagado');

                    $this->carteraRepository->updateVenta($venta, [
                        'saldo_pendiente' => $nuevoSaldo,
                        'estado_pago' => $estadoPago,
                    ]);
                }
                $detalle->delete();
            }

            foreach ($abono->aplicacionesSaldoInicial as $aplicacion) {
                $saldoInicial = $aplicacion->saldoInicial;
                if ($saldoInicial) {
                    $nuevoSaldo = round((float) $saldoInicial->saldo_pendiente + (float) $aplicacion->valor_aplicado, 2);
                    $original = (float) $saldoInicial->valor_original;
                    $estado = $nuevoSaldo >= $original ? 'pendiente' : ($nuevoSaldo > 0 ? 'parcial' : 'pagado');

                    $this->carteraRepository->updateSaldoInicial($saldoInicial, [
                        'saldo_pendiente' => $nuevoSaldo,
                        'estado' => $estado,
                    ]);
                }
                $aplicacion->delete();
            }

            $cliente = $this->carteraRepository->findClienteById($abono->cliente_id);
            if ($cliente) {
                $saldoAnterior = (float) $cliente->saldo_credito;
                $saldoNuevo = round($saldoAnterior + (float) $abono->valor, 2);

                $this->carteraRepository->updateCliente($cliente, [
                    'saldo_credito' => $saldoNuevo,
                ]);

                $this->carteraRepository->createMovimientoCartera([
                    'cliente_id' => $cliente->id,
                    'tipo_movimiento' => 'anulacion_abono',
                    'origen_modulo' => 'abonos_cartera',
                    'origen_id' => $abono->id,
                    'valor' => $abono->valor,
                    'saldo_anterior' => $saldoAnterior,
                    'saldo_nuevo' => $saldoNuevo,
                    'medio_pago' => $abono->medio_pago,
                    'descripcion' => "Anulación de abono #{$abono->id}: {$motivo}",
                    'user_id' => $userId,
                    'fecha_movimiento' => now(),
                ]);
            }

            $abono->update([
                'estado' => 'anulado',
                'observacion' => trim($abono->observacion . " [ANULADO: {$motivo}]"),
            ]);

            if ($abono->turno_islero_id) {
                $totalesTurno = $this->turnoRepository->recalcularTotalesTurno($abono->turno_islero_id);
                $abono->turnoIslero?->update($totalesTurno);
            }

            return $abono->fresh(['cliente', 'caja', 'usuario', 'turnoIslero']);
        });
    }

    public function updateAbono(int $id, array $data, int $userId): AbonoCartera
    {
        return DB::transaction(function () use ($id, $data, $userId) {
            $abono = AbonoCartera::with(['detalles.venta', 'aplicacionesSaldoInicial.saldoInicial', 'turnoIslero'])
                ->find($id);

            if (!$abono) {
                throw new HttpException(404, 'Abono de cartera no encontrado.');
            }

            if ($abono->estado === 'anulado') {
                throw new HttpException(422, 'No se puede editar un abono anulado.');
            }

            if ($abono->turno_islero_id) {
                $turno = $abono->turnoIslero;
                if ($turno && $turno->estado === 'cerrado') {
                    throw new HttpException(
                        422,
                        'No se puede editar el abono porque el turno de islero ya se encuentra cerrado.'
                    );
                }
            }

            $nuevoValor = isset($data['valor']) ? (float) $data['valor'] : (float) $abono->valor;
            $nuevoMedioPago = $data['medio_pago'] ?? $abono->medio_pago;
            $nuevaObservacion = $data['observacion'] ?? $abono->observacion;

            $nuevaCajaId = $abono->caja_id;
            if (array_key_exists('caja_id', $data)) {
                if (!empty($data['caja_id'])) {
                    $caja = $this->carteraRepository->findCajaById((int) $data['caja_id']);
                    if (!$caja) {
                        throw new HttpException(404, 'Caja no encontrada.');
                    }
                    $nuevaCajaId = $caja->id;
                } else {
                    $nuevaCajaId = null;
                }
            }

            // Sincronizar / actualizar movimiento de caja de cartera si existe
            $movimientoCaja = \App\Models\MovimientoCaja::query()
                ->where('origen_modulo', 'cartera')
                ->where('origen_id', $abono->id)
                ->where('categoria_movimiento', 'abono_cartera')
                ->first();

            if ($movimientoCaja) {
                $updateMovData = [
                    'monto' => $nuevoValor,
                    'medio_pago' => $nuevoMedioPago,
                ];
                if ($nuevaCajaId) {
                    $updateMovData['caja_id'] = $nuevaCajaId;
                }
                $movimientoCaja->update($updateMovData);
            } elseif ($nuevaCajaId && !$abono->turno_islero_id) {
                $this->carteraRepository->createMovimientoCaja([
                    'caja_id' => $nuevaCajaId,
                    'tipo_movimiento' => 'ingreso',
                    'categoria_movimiento' => 'abono_cartera',
                    'origen_modulo' => 'cartera',
                    'origen_id' => $abono->id,
                    'medio_pago' => $nuevoMedioPago,
                    'monto' => $nuevoValor,
                    'descripcion' => 'Abono cartera cliente #' . $abono->cliente_id,
                    'user_id' => $userId,
                    'fecha_movimiento' => now(),
                ]);
            }

            if ($abono->estado === 'pendiente') {
                $updateData = [
                    'valor' => $nuevoValor,
                    'medio_pago' => $nuevoMedioPago,
                    'observacion' => $nuevaObservacion,
                    'caja_id' => $nuevaCajaId,
                ];

                if (isset($data['cliente_id'])) {
                    $nuevoCliente = $this->carteraRepository->findClienteById((int) $data['cliente_id']);
                    if (!$nuevoCliente) {
                        throw new HttpException(404, 'Cliente no encontrado.');
                    }
                    $updateData['cliente_id'] = $nuevoCliente->id;
                }

                $abono->update($updateData);

                if ($abono->turno_islero_id) {
                    $totalesTurno = $this->turnoRepository->recalcularTotalesTurno($abono->turno_islero_id);
                    $abono->turnoIslero?->update($totalesTurno);
                }

                return $abono->fresh(['cliente', 'caja', 'usuario', 'turnoIslero', 'detalles.venta']);
            }

            foreach ($abono->detalles as $detalle) {
                $venta = $detalle->venta;
                if ($venta) {
                    $nuevoSaldo = round((float) $venta->saldo_pendiente + (float) $detalle->valor_aplicado, 2);
                    $totalVenta = (float) $venta->total;
                    $estadoPago = $nuevoSaldo >= $totalVenta ? 'pendiente' : ($nuevoSaldo > 0 ? 'parcial' : 'pagado');

                    $this->carteraRepository->updateVenta($venta, [
                        'saldo_pendiente' => $nuevoSaldo,
                        'estado_pago' => $estadoPago,
                    ]);
                }
                $detalle->delete();
            }

            foreach ($abono->aplicacionesSaldoInicial as $aplicacion) {
                $saldoInicial = $aplicacion->saldoInicial;
                if ($saldoInicial) {
                    $nuevoSaldo = round((float) $saldoInicial->saldo_pendiente + (float) $aplicacion->valor_aplicado, 2);
                    $original = (float) $saldoInicial->valor_original;
                    $estado = $nuevoSaldo >= $original ? 'pendiente' : ($nuevoSaldo > 0 ? 'parcial' : 'pagado');

                    $this->carteraRepository->updateSaldoInicial($saldoInicial, [
                        'saldo_pendiente' => $nuevoSaldo,
                        'estado' => $estado,
                    ]);
                }
                $aplicacion->delete();
            }

            $nuevoValor = isset($data['valor']) ? (float) $data['valor'] : (float) $abono->valor;
            $nuevoMedioPago = $data['medio_pago'] ?? $abono->medio_pago;
            $nuevaObservacion = $data['observacion'] ?? $abono->observacion;

            $cliente = $this->carteraRepository->findClienteById($abono->cliente_id);
            if (!$cliente) {
                throw new HttpException(404, 'Cliente no encontrado.');
            }

            $abono->update([
                'valor' => $nuevoValor,
                'medio_pago' => $nuevoMedioPago,
                'observacion' => $nuevaObservacion,
                'caja_id' => $nuevaCajaId,
            ]);

            $ventasPendientes = $this->carteraRepository->getVentasPendientesCliente($cliente->id);
            $saldosInicialesPendientes = $this->carteraRepository->getSaldosInicialesPendientesCliente($cliente->id);

            $deudas = collect();
            foreach ($ventasPendientes as $venta) {
                $deudas->push([
                    'tipo' => 'venta',
                    'id' => $venta->id,
                    'fecha' => $venta->fecha_venta,
                    'documento' => $venta,
                    'saldo_pendiente' => (float) $venta->saldo_pendiente,
                ]);
            }
            foreach ($saldosInicialesPendientes as $saldoInicial) {
                $deudas->push([
                    'tipo' => 'saldo_inicial',
                    'id' => $saldoInicial->id,
                    'fecha' => $saldoInicial->fecha_documento,
                    'documento' => $saldoInicial,
                    'saldo_pendiente' => (float) $saldoInicial->saldo_pendiente,
                ]);
            }

            $deudas = $deudas->sortBy(function ($deuda) {
                $fecha = $deuda['fecha'];
                return $fecha instanceof \Carbon\CarbonInterface ? $fecha->timestamp : strtotime((string) $fecha);
            })->values();

            $valorPendienteAbono = $nuevoValor;

            foreach ($deudas as $deuda) {
                if ($valorPendienteAbono <= 0) break;
                $saldoDeuda = (float) $deuda['saldo_pendiente'];
                if ($saldoDeuda <= 0) continue;

                $valorAplicado = min($saldoDeuda, $valorPendienteAbono);
                $nuevoSaldo = max(0, round($saldoDeuda - $valorAplicado, 2));

                if ($deuda['tipo'] === 'venta') {
                    $venta = $deuda['documento'];
                    $estadoPago = $nuevoSaldo <= 0 ? 'pagado' : 'parcial';
                    $this->carteraRepository->updateVenta($venta, [
                        'saldo_pendiente' => $nuevoSaldo,
                        'estado_pago' => $estadoPago,
                    ]);
                    $this->carteraRepository->createAbonoDetalle(
                        new CreateAbonoCarteraDetalleDTO(
                            abono_cartera_id: $abono->id,
                            venta_id: $venta->id,
                            valor_aplicado: $valorAplicado
                        )
                    );
                }

                if ($deuda['tipo'] === 'saldo_inicial') {
                    $saldoInicial = $deuda['documento'];
                    $estado = $nuevoSaldo <= 0 ? 'pagado' : 'parcial';
                    $this->carteraRepository->updateSaldoInicial($saldoInicial, [
                        'saldo_pendiente' => $nuevoSaldo,
                        'estado' => $estado,
                    ]);
                    $this->carteraRepository->createAplicacionSaldoInicial([
                        'abono_cartera_id' => $abono->id,
                        'saldo_inicial_id' => $saldoInicial->id,
                        'valor_aplicado' => $valorAplicado,
                    ]);
                }

                $valorPendienteAbono = round($valorPendienteAbono - $valorAplicado, 2);
            }

            $saldoVentas = (float) $this->carteraRepository->getVentasPendientesCliente($cliente->id)->sum('saldo_pendiente');
            $saldoIniciales = (float) $this->carteraRepository->getSaldosInicialesPendientesCliente($cliente->id)->sum('saldo_pendiente');
            $saldoNuevoCliente = round($saldoVentas + $saldoIniciales, 2);

            $saldoAnteriorCliente = (float) $cliente->saldo_credito;
            $this->carteraRepository->updateCliente($cliente, ['saldo_credito' => $saldoNuevoCliente]);

            $this->carteraRepository->createMovimientoCartera([
                'cliente_id' => $cliente->id,
                'tipo_movimiento' => 'edicion_abono',
                'origen_modulo' => 'abonos_cartera',
                'origen_id' => $abono->id,
                'valor' => $nuevoValor,
                'saldo_anterior' => $saldoAnteriorCliente,
                'saldo_nuevo' => $saldoNuevoCliente,
                'medio_pago' => $nuevoMedioPago,
                'descripcion' => "Edición de abono #{$abono->id}",
                'user_id' => $userId,
                'fecha_movimiento' => now(),
            ]);

            if ($abono->turno_islero_id) {
                $totalesTurno = $this->turnoRepository->recalcularTotalesTurno($abono->turno_islero_id);
                $abono->turnoIslero?->update($totalesTurno);
            }

            return $abono->fresh(['cliente', 'caja', 'usuario', 'turnoIslero', 'detalles.venta']);
        });
    }

    public function procesarAbonosPendientesTurno(int $turnoId, int $userId): void
    {
        $abonosPendientes = AbonoCartera::query()
            ->where('turno_islero_id', $turnoId)
            ->where('estado', 'pendiente')
            ->orderBy('id', 'asc')
            ->get();

        if ($abonosPendientes->isEmpty()) {
            return;
        }

        foreach ($abonosPendientes as $abono) {
            $cliente = $this->carteraRepository->findClienteById($abono->cliente_id);

            if (!$cliente) {
                throw new HttpException(
                    404,
                    "El cliente #{$abono->cliente_id} asociado al abono pendiente #{$abono->id} no existe."
                );
            }

            $ventasPendientes = $this->carteraRepository->getVentasPendientesCliente($cliente->id);
            $saldosInicialesPendientes = $this->carteraRepository->getSaldosInicialesPendientesCliente($cliente->id);

            $deudas = collect();
            foreach ($ventasPendientes as $venta) {
                $deudas->push([
                    'tipo' => 'venta',
                    'id' => $venta->id,
                    'fecha' => $venta->fecha_venta,
                    'documento' => $venta,
                    'saldo_pendiente' => (float) $venta->saldo_pendiente,
                ]);
            }
            foreach ($saldosInicialesPendientes as $saldoInicial) {
                $deudas->push([
                    'tipo' => 'saldo_inicial',
                    'id' => $saldoInicial->id,
                    'fecha' => $saldoInicial->fecha_documento,
                    'documento' => $saldoInicial,
                    'saldo_pendiente' => (float) $saldoInicial->saldo_pendiente,
                ]);
            }

            $deudas = $deudas->sortBy(function ($deuda) {
                $fecha = $deuda['fecha'];
                return $fecha instanceof \Carbon\CarbonInterface ? $fecha->timestamp : strtotime((string) $fecha);
            })->values();

            $deudaTotalDisponible = (float) $deudas->sum('saldo_pendiente');
            $valorAbono = (float) $abono->valor;

            if ($deudaTotalDisponible < $valorAbono - 0.001) {
                $nombreCliente = $cliente->nombre ?: "ID #{$cliente->id}";
                throw new HttpException(
                    422,
                    "El abono pendiente #{$abono->id} de {$nombreCliente} por $" . number_format($valorAbono, 2) . " no puede aplicarse completamente porque la deuda disponible ($" . number_format($deudaTotalDisponible, 2) . ") es insuficiente."
                );
            }

            $saldoAnteriorCliente = (float) $cliente->saldo_credito;
            $valorPendienteAbono = $valorAbono;

            foreach ($deudas as $deuda) {
                if ($valorPendienteAbono <= 0) {
                    break;
                }

                $saldoDeuda = (float) $deuda['saldo_pendiente'];
                if ($saldoDeuda <= 0) {
                    continue;
                }

                $valorAplicado = min($saldoDeuda, $valorPendienteAbono);
                $nuevoSaldo = max(0, round($saldoDeuda - $valorAplicado, 2));

                if ($deuda['tipo'] === 'venta') {
                    $venta = $deuda['documento'];
                    $estadoPago = $nuevoSaldo <= 0 ? 'pagado' : 'parcial';

                    $this->carteraRepository->updateVenta($venta, [
                        'saldo_pendiente' => $nuevoSaldo,
                        'estado_pago' => $estadoPago,
                    ]);

                    $this->carteraRepository->createAbonoDetalle(
                        new CreateAbonoCarteraDetalleDTO(
                            abono_cartera_id: $abono->id,
                            venta_id: $venta->id,
                            valor_aplicado: $valorAplicado
                        )
                    );
                }

                if ($deuda['tipo'] === 'saldo_inicial') {
                    $saldoInicial = $deuda['documento'];
                    $estado = $nuevoSaldo <= 0 ? 'pagado' : 'parcial';

                    $this->carteraRepository->updateSaldoInicial($saldoInicial, [
                        'saldo_pendiente' => $nuevoSaldo,
                        'estado' => $estado,
                    ]);

                    $this->carteraRepository->createAplicacionSaldoInicial([
                        'abono_cartera_id' => $abono->id,
                        'saldo_inicial_id' => $saldoInicial->id,
                        'valor_aplicado' => $valorAplicado,
                    ]);
                }

                $valorPendienteAbono = round($valorPendienteAbono - $valorAplicado, 2);
            }

            if ($valorPendienteAbono > 0.001) {
                $nombreCliente = $cliente->nombre ?: "ID #{$cliente->id}";
                throw new HttpException(
                    422,
                    "El abono pendiente #{$abono->id} de {$nombreCliente} por $" . number_format($valorAbono, 2) . " no pudo ser aplicado completamente."
                );
            }

            $saldoVentas = (float) $this->carteraRepository->getVentasPendientesCliente($cliente->id)->sum('saldo_pendiente');
            $saldoIniciales = (float) $this->carteraRepository->getSaldosInicialesPendientesCliente($cliente->id)->sum('saldo_pendiente');
            $saldoNuevoCliente = round($saldoVentas + $saldoIniciales, 2);

            $this->carteraRepository->updateCliente($cliente, [
                'saldo_credito' => $saldoNuevoCliente,
            ]);

            $this->carteraRepository->createMovimientoCartera([
                'cliente_id' => $cliente->id,
                'tipo_movimiento' => 'abono',
                'origen_modulo' => 'abonos_cartera',
                'origen_id' => $abono->id,
                'valor' => $valorAbono,
                'saldo_anterior' => $saldoAnteriorCliente,
                'saldo_nuevo' => $saldoNuevoCliente,
                'medio_pago' => $abono->medio_pago,
                'descripcion' => $abono->observacion
                    ?: 'Abono aplicado automáticamente al cerrar el turno.',
                'user_id' => $userId,
                'fecha_movimiento' => now(),
            ]);

            $abono->update([
                'estado' => 'aplicado',
                'fecha_aplicacion' => now(),
                'user_aplicacion_id' => $userId,
            ]);
        }
    }
}