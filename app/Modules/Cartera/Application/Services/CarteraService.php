<?php

namespace App\Modules\Cartera\Application\Services;

use App\Models\Cliente;
use App\Models\AbonoCartera;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Cartera\Application\DTOs\CreateAbonoCarteraDTO;
use App\Modules\Cartera\Application\Interfaces\CarteraRepositoryInterface;

class CarteraService
{
    private const DESTINO_CARTERA = 3;

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
            $cliente = $this->carteraRepository->findClienteById($dto->cliente_id);

            if (!$cliente) {
                throw new HttpException(404, 'Cliente no encontrado.');
            }

            if (!(bool) $cliente->maneja_credito) {
                throw new HttpException(422, 'El cliente no tiene crédito habilitado.');
            }

            if ((float) $cliente->saldo_credito <= 0) {
                throw new HttpException(422, 'El cliente no tiene saldo pendiente.');
            }

            if ($dto->valor > (float) $cliente->saldo_credito) {
                throw new HttpException(422, 'El abono no puede superar el saldo pendiente.');
            }

            $tipoCaja = $dto->medio_pago === 'efectivo' ? 'efectivo' : 'digital';

            $caja = $this->carteraRepository->getCajaAbiertaByTipoAndDestino( $tipoCaja, self::DESTINO_CARTERA);

            if (!$caja) {
                throw new HttpException(422, "No hay caja {$tipoCaja} abierta.");
            }

            $saldoAnterior = (float) $cliente->saldo_credito;
            $saldoNuevo = $saldoAnterior - $dto->valor;

            $turnoAbierto = $this->carteraRepository->getTurnoAbiertoByUser($dto->user_id);

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

            $this->carteraRepository->updateCliente($cliente, [
                'saldo_credito' => $saldoNuevo,
            ]);

            $this->carteraRepository->createMovimientoCartera([
                'cliente_id' => $cliente->id,
                'tipo_movimiento' => 'abono',
                'origen_modulo' => 'abonos_cartera',
                'origen_id' => $abono->id,
                'valor' => $dto->valor,
                'saldo_anterior' => $saldoAnterior,
                'saldo_nuevo' => $saldoNuevo,
                'medio_pago' => $dto->medio_pago,
                'descripcion' => $dto->observacion ?: 'Abono de cartera',
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

            return $abono->fresh()->load(['cliente', 'caja', 'usuario']);
        });
    }
}