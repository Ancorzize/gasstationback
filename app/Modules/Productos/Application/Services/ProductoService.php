<?php

namespace App\Modules\Productos\Application\Services;

use App\Models\Producto;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Productos\Application\DTOs\CreateProductoDTO;
use App\Modules\Productos\Application\DTOs\UpdateProductoDTO;
use App\Modules\Productos\Application\Interfaces\ProductoRepositoryInterface;
use App\Modules\Facturacion\Application\Services\CatalogosFacturacionService;

class ProductoService
{
    public function __construct(
        protected ProductoRepositoryInterface $productoRepository,
        protected ?CatalogosFacturacionService $catalogosService = null
    ) {
        $this->catalogosService = $catalogosService ?? app(CatalogosFacturacionService::class);
    }

    public function paginate(
        array $filters = [],
        ?int $bodegaId = null
    )
    {
        return $this->productoRepository->paginate(
            $filters,
            5000,
            $bodegaId
        );
    }

    public function findById(int $id): Producto
    {
        $producto = $this->productoRepository->findById($id);

        if (!$producto) {
            throw new HttpException(404, 'Producto no encontrado.');
        }

        return $producto;
    }

    public function create(CreateProductoDTO $dto): Producto
    {
        $configFiscal = $this->validarYNormalizarClasificacionTributaria(
            $dto->tipo_impuesto,
            $dto->porcentaje_iva,
            $dto->codigo_impuesto
        );

        return $this->productoRepository->create([
            'codigo' => $dto->codigo,
            'nombre' => $dto->nombre,
            'descripcion' => $dto->descripcion,
            'marca_id' => $dto->marca_id,
            'categoria_producto_id' => $dto->categoria_producto_id,
            'unidad_medida_id' => $dto->unidad_medida_id,
            'precio_compra' => $dto->precio_compra,
            'precio_venta' => $dto->precio_venta,
            'tipo_impuesto' => $configFiscal['tipo_impuesto'],
            'porcentaje_iva' => $configFiscal['porcentaje_iva'],
            'codigo_impuesto' => $configFiscal['codigo_impuesto'],
            'permite_decimal' => $dto->permite_decimal,
            'is_active' => true,
            'codigo_barras' => $dto->codigo_barras,
        ]);
    }

    public function update(int $id, UpdateProductoDTO $dto): Producto
    {
        $producto = $this->findById($id);

        $configFiscal = $this->validarYNormalizarClasificacionTributaria(
            $dto->tipo_impuesto,
            $dto->porcentaje_iva,
            $dto->codigo_impuesto
        );

        return $this->productoRepository->update($producto, [
            'codigo' => $dto->codigo,
            'codigo_barras' => $dto->codigo_barras,
            'nombre' => $dto->nombre,
            'descripcion' => $dto->descripcion,
            'marca_id' => $dto->marca_id,
            'categoria_producto_id' => $dto->categoria_producto_id,
            'unidad_medida_id' => $dto->unidad_medida_id,
            'precio_compra' => $dto->precio_compra,
            'precio_venta' => $dto->precio_venta,
            'tipo_impuesto' => $configFiscal['tipo_impuesto'],
            'porcentaje_iva' => $configFiscal['porcentaje_iva'],
            'codigo_impuesto' => $configFiscal['codigo_impuesto'],
            'permite_decimal' => $dto->permite_decimal,
        ]);
    }

    /**
     * Validar estrictamente la clasificación tributaria y resolver su código homologado.
     */
    protected function validarYNormalizarClasificacionTributaria(
        string $tipoImpuesto,
        float $porcentajeIva,
        ?string $codigoImpuesto = null
    ): array {
        $tipoNormalized = strtolower(trim($tipoImpuesto));

        if (!in_array($tipoNormalized, Producto::TIPOS_IMPUESTO_PERMITIDOS, true)) {
            throw new HttpException(422, 'La clasificación tributaria seleccionada no es válida.');
        }

        $tarifa = round($porcentajeIva, 2);

        if ($tipoNormalized === Producto::TIPO_IMPUESTO_GRAVADO) {
            if ($tarifa <= 0 || $tarifa > 100) {
                throw new HttpException(
                    422,
                    'Un producto gravado debe tener una tarifa de IVA superior a 0% y hasta 100%.'
                );
            }
        } else {
            if ($tarifa != 0.0) {
                throw new HttpException(
                    422,
                    "Un producto clasificado como {$tipoNormalized} debe tener tarifa de IVA igual a 0%."
                );
            }
            $tarifa = 0.0;
        }

        try {
            $codigoResuelto = $this->catalogosService?->resolverCodigoImpuestoProducto(
                $tipoNormalized,
                $codigoImpuesto
            ) ?? ($tipoNormalized === Producto::TIPO_IMPUESTO_EXCLUIDO ? null : '1');
        } catch (\InvalidArgumentException $e) {
            throw new HttpException(422, $e->getMessage());
        }

        return [
            'tipo_impuesto' => $tipoNormalized,
            'porcentaje_iva' => $tarifa,
            'codigo_impuesto' => $codigoResuelto,
        ];
    }

    public function changeStatus(int $id, bool $isActive): Producto
    {
        $producto = $this->findById($id);

        return $this->productoRepository->changeStatus($producto, $isActive);
    }

    public function delete(int $id): void
    {
        $producto = $this->findById($id);

        $this->productoRepository->delete($producto);
    }
}