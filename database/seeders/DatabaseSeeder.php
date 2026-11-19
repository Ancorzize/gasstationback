<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Orquestador maestro para inicialización y reconstrucción de la base de datos.
     * Orden estricto según el grafo de dependencias de claves foráneas.
     */
    public function run(): void
    {
        $this->call([
            // 1. Catálogos geográficos y base territorial
            UbicacionSeeder::class,

            // 2. Destinos de recaudo y estructura física de almacenamiento
            DestinosRecaudoSeeder::class,
            BodegasSeeder::class,

            // 3. Seguridad, usuarios, roles, permisos y widgets de panel
            SeguridadSeeder::class,

            // 4. Catálogos base de productos y gastos
            MarcasSeeder::class,
            CategoriasProductoSeeder::class,
            CategoriasGastoSeeder::class,
            UnidadesMedidaSeeder::class,

            // 5. Catálogo de productos y servicios
            ProductosSeeder::class,
            ServiciosSeeder::class,

            // 6. Terceros (Clientes y Proveedores) con saldos en cero
            ClientesSeeder::class,
            ProveedoresSeeder::class,

            // 7. Infraestructura de combustible (Estaciones -> Bombas -> Mangueras)
            EstacionesSeeder::class,
            BombasSeeder::class,
            ManguerasSeeder::class,

            // 8. Configuración empresarial y tributaria
            ConfiguracionEmpresaSeeder::class,
            ConfiguracionFacturacionSeeder::class,
            ResolucionesFacturacionSeeder::class,
            MapeosCatalogosFacturacionSeeder::class,

            // 9. Precios vigentes de combustible
            PreciosCombustibleSeeder::class,

            // 10. Existencias de inventario inicializadas en 0.00
            InventariosSeeder::class,

            // 11. Parámetros legales y ciclos de nómina
            NominaParametrosSeeder::class,
        ]);
    }
}
