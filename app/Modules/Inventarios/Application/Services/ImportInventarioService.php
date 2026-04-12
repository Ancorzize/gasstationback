<?php

namespace App\Modules\Inventarios\Application\Services;

use Illuminate\Support\Facades\DB;
use App\Modules\Inventarios\Application\DTOs\ImportInventarioFilaDTO;
use App\Modules\Inventarios\Application\Interfaces\InventarioImportRepositoryInterface;

class ImportInventarioService
{
    public function __construct(
        protected InventarioImportRepositoryInterface $repository
    ) {}

    public function importar(array $items, int $userId): array
    {
        $procesadas = 0;
        $exitosas = 0;
        $fallidas = 0;
        $errores = [];

        foreach ($items as $index => $row) {
            $procesadas++;

            try {
                $dto = $this->mapRowToDTO($row, $userId, $index);

                DB::transaction(function () use ($dto) {
                    $producto = $this->repository->findProductoByCodigo($dto->codigo_producto);

                    if (!$producto) {
                        throw new \RuntimeException('Producto no encontrado.');
                    }

                    if (!(bool) $producto->is_active) {
                        throw new \RuntimeException('El producto está inactivo.');
                    }

                    $bodega = $this->repository->findBodegaByCodigo($dto->bodega_codigo);

                    if (!$bodega) {
                        throw new \RuntimeException('Bodega no encontrada.');
                    }

                    if (!(bool) $bodega->is_active) {
                        throw new \RuntimeException('La bodega está inactiva.');
                    }

                    $inventario = $this->repository->findInventario($producto->id, $bodega->id);

                    if (!$inventario) {
                        $this->repository->createInventario([
                            'producto_id' => $producto->id,
                            'bodega_id' => $bodega->id,
                            'cantidad' => 0,
                        ]);
                    }

                    $this->repository->incrementInventario(
                        $producto->id,
                        $bodega->id,
                        $dto->cantidad
                    );

                    $this->repository->createMovimiento([
                        'tipo_movimiento' => 'entrada',
                        'producto_id' => $producto->id,
                        'bodega_origen_id' => null,
                        'bodega_destino_id' => $bodega->id,
                        'cantidad' => $dto->cantidad,
                        'observacion' => $dto->observacion,
                        'user_id' => $dto->user_id,
                    ]);
                });

                $exitosas++;
            } catch (\Throwable $e) {
                $fallidas++;

                $errores[] = [
                    'fila' => $row['fila'] ?? ($index + 1),
                    'codigo_producto' => $row['codigo_producto'] ?? null,
                    'bodega_codigo' => $row['bodega_codigo'] ?? null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'procesadas' => $procesadas,
            'exitosas' => $exitosas,
            'fallidas' => $fallidas,
            'errores' => $errores,
        ];
    }

    private function mapRowToDTO(array $row, int $userId, int $index): ImportInventarioFilaDTO
    {
        $codigoProducto = trim((string) ($row['codigo_producto'] ?? ''));
        $bodegaCodigo = trim((string) ($row['bodega_codigo'] ?? ''));
        $cantidad = $row['cantidad'] ?? null;
        $observacion = $row['observacion'] ?? null;
        $fila = (int) ($row['fila'] ?? ($index + 1));

        if ($codigoProducto === '') {
            throw new \RuntimeException('El código del producto es obligatorio.');
        }

        if ($bodegaCodigo === '') {
            throw new \RuntimeException('El código de la bodega es obligatorio.');
        }

        if (!is_numeric($cantidad)) {
            throw new \RuntimeException('La cantidad debe ser numérica.');
        }

        $cantidad = (float) $cantidad;

        if ($cantidad <= 0) {
            throw new \RuntimeException('La cantidad debe ser mayor a cero.');
        }

        return new ImportInventarioFilaDTO(
            fila: $fila,
            codigo_producto: $codigoProducto,
            bodega_codigo: $bodegaCodigo,
            cantidad: $cantidad,
            observacion: $observacion ?: null,
            user_id: $userId,
        );
    }
}