<?php

namespace App\Modules\Facturacion\Infrastructure\Managers;

use App\Modules\Facturacion\Application\Interfaces\FacturacionProviderInterface;
use App\Modules\Facturacion\Infrastructure\Providers\MatiasProvider;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use RuntimeException;

/**
 * Manager/Factory para resolver dinámicamente el proveedor de facturación electrónica.
 * Desacopla FacturacionService de cualquier clase concreta del proveedor.
 */
class FacturacionManager
{
    /**
     * Resolvers personalizados registrados para proveedores de facturación.
     * @var array<string, callable>
     */
    protected array $customProviders = [];

    public function __construct(
        protected Container $container
    ) {}

    /**
     * Resolver la instancia del proveedor según su clave y ambiente.
     *
     * @param string $providerName Nombre/código del proveedor (ej: 'matias')
     * @param string $ambiente Ambiente ('sandbox' | 'produccion')
     * @return FacturacionProviderInterface
     */
    public function make(string $providerName, string $ambiente = 'sandbox'): FacturacionProviderInterface
    {
        $providerKey = strtolower(trim($providerName));

        if (isset($this->customProviders[$providerKey])) {
            return call_user_func($this->customProviders[$providerKey], $this->container, $ambiente);
        }

        return match ($providerKey) {
            'matias' => $this->resolveMatiasProvider($ambiente),
            default => throw new InvalidArgumentException(
                "El proveedor de facturación electrónica '{$providerName}' no está soportado o registrado."
            ),
        };
    }

    /**
     * Instanciar el proveedor MATIAS con su ambiente.
     */
    protected function resolveMatiasProvider(string $ambiente): FacturacionProviderInterface
    {
        return new MatiasProvider($ambiente);
    }

    /**
     * Permitir la extensión o registro dinámico de nuevos proveedores de facturación.
     */
    public function extend(string $providerName, callable $resolver): self
    {
        $this->customProviders[strtolower(trim($providerName))] = $resolver;
        return $this;
    }
}
