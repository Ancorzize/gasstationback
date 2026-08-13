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
class CarteraService
{
    public function __construct(
        protected CarteraRepositoryInterface $carteraRepository
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

            $saldoAnterior = (float) $cliente->saldo_credito;

            $abono = $this->carteraRepository->createAbono([

                'cliente_id' => $cliente->id,

                'caja_id' => $caja->id,

                'fecha_abono' => $dto->fecha_abono,

                'valor' => $dto->valor,

                'medio_pago' => $dto->medio_pago,

                'observacion' => $dto->observacion,

                'estado' => 'registrado',

                'user_id' => $dto->user_id,

                'turno_islero_id' => $turnoAbierto?->id,

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
}