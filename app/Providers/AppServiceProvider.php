<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Auth\Application\Interfaces\AuthRepositoryInterface;
use App\Modules\Auth\Infrastructure\Repositories\AuthRepository;
use App\Modules\Usuarios\Application\Interfaces\UserRepositoryInterface;
use App\Modules\Usuarios\Infrastructure\Repositories\UserRepository;
use App\Modules\Clientes\Application\Interfaces\ClienteRepositoryInterface;
use App\Modules\Clientes\Infrastructure\Repositories\ClienteRepository;
use App\Modules\Roles\Application\Interfaces\RoleRepositoryInterface;
use App\Modules\Roles\Infrastructure\Repositories\RoleRepository;
use App\Modules\Proveedores\Application\Interfaces\ProveedorRepositoryInterface;
use App\Modules\Proveedores\Infrastructure\Repositories\ProveedorRepository;
use App\Modules\Marcas\Application\Interfaces\MarcaRepositoryInterface;
use App\Modules\Marcas\Infrastructure\Repositories\MarcaRepository;
use App\Modules\CategoriasProducto\Application\Interfaces\CategoriaProductoRepositoryInterface;
use App\Modules\CategoriasProducto\Infrastructure\Repositories\CategoriaProductoRepository;
use App\Modules\UnidadesMedida\Application\Interfaces\UnidadMedidaRepositoryInterface;
use App\Modules\UnidadesMedida\Infrastructure\Repositories\UnidadMedidaRepository;
use App\Modules\Productos\Application\Interfaces\ProductoRepositoryInterface;
use App\Modules\Productos\Infrastructure\Repositories\ProductoRepository;
use App\Modules\Servicios\Application\Interfaces\ServicioRepositoryInterface;
use App\Modules\Servicios\Infrastructure\Repositories\ServicioRepository;
use App\Modules\Ubicaciones\Application\Interfaces\UbicacionRepositoryInterface;
use App\Modules\Ubicaciones\Infrastructure\Repositories\UbicacionRepository;
use App\Modules\ConfiguracionEmpresa\Application\Interfaces\ConfiguracionEmpresaRepositoryInterface;
use App\Modules\ConfiguracionEmpresa\Infrastructure\Repositories\ConfiguracionEmpresaRepository;
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ClienteRepositoryInterface::class, ClienteRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(ProveedorRepositoryInterface::class, ProveedorRepository::class);
        $this->app->bind(MarcaRepositoryInterface::class, MarcaRepository::class);
        $this->app->bind(CategoriaProductoRepositoryInterface::class, CategoriaProductoRepository::class);
        $this->app->bind(UnidadMedidaRepositoryInterface::class,UnidadMedidaRepository::class);
        $this->app->bind(ProductoRepositoryInterface::class, ProductoRepository::class);
        $this->app->bind(ServicioRepositoryInterface::class, ServicioRepository::class);
        $this->app->bind(UbicacionRepositoryInterface::class, UbicacionRepository::class);
        $this->app->bind(ConfiguracionEmpresaRepositoryInterface::class, ConfiguracionEmpresaRepository::class);
    }

    public function boot(): void
    {
        //
    }
}