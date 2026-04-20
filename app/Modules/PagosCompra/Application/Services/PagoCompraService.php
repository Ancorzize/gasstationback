<?php

namespace App\Modules\PagosCompra\Application\Services;

use App\Models\PagoCompra;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\PagosCompra\Application\Interfaces\PagoCompraRepositoryInterface;

class PagoCompraService
{
    public function __construct(
        protected PagoCompraRepositoryInterface $pagoCompraRepository
    ) {}

    public function paginate(array $filters = [], int $perPage = 10)
    {
        return $this->pagoCompraRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): PagoCompra
    {
        $pago = $this->pagoCompraRepository->findById($id);

        if (!$pago) {
            throw new HttpException(404, 'Pago de compra no encontrado.');
        }

        return $pago;
    }
}