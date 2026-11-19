<?php

namespace Database\Seeders;

use App\Models\MapeoCatalogoFacturacion;
use Illuminate\Database\Seeder;

/**
 * Seeder idempotente para inicializar las homologaciones fiscales verificadas
 * del proveedor MATÍAS en la tabla mapeos_catalogos_facturacion.
 *
 * Ejecución manual:
 * php artisan db:seed --class=MatiasCatalogMappingsSeeder
 */
class MatiasCatalogMappingsSeeder extends Seeder
{
    /**
     * Ejecutar el seeder de equivalencias de catálogos fiscales MATÍAS.
     */
    public function run(): void
    {
        $registrosAutorizados = [
            // Registro 1 — Unidad de medida: Mililitro
            [
                'proveedor' => 'matias',
                'categoria' => 'unidad_medida',
                'codigo_interno' => 'mlt',
                'codigo_externo' => '852',
                'codigo_externo_secundario' => 'MLT',
                'descripcion' => 'Unidad de medida Mililitro (MATÍAS ID 852 / DIAN MLT)',
                'is_active' => true,
            ],

            // Registro 2 — Unidad de medida: Litro
            [
                'proveedor' => 'matias',
                'categoria' => 'unidad_medida',
                'codigo_interno' => 'litro',
                'codigo_externo' => '821',
                'codigo_externo_secundario' => 'LTR',
                'descripcion' => 'Unidad de medida Litro (MATÍAS ID 821 / DIAN LTR)',
                'is_active' => true,
            ],

            // Registro 3 — Medio de pago: Efectivo
            [
                'proveedor' => 'matias',
                'categoria' => 'medio_pago',
                'codigo_interno' => 'efectivo',
                'codigo_externo' => '10',
                'codigo_externo_secundario' => '1',
                'descripcion' => 'Pago de contado en efectivo (MATÍAS/DIAN)',
                'is_active' => true,
            ],

            // Registro 4 — Impuesto: IVA (opcional)
            [
                'proveedor' => 'matias',
                'categoria' => 'impuesto',
                'codigo_interno' => 'iva',
                'codigo_externo' => '1',
                'codigo_externo_secundario' => '01',
                'descripcion' => 'Impuesto sobre las ventas IVA (MATÍAS tax_id 1 / DIAN 01)',
                'is_active' => true,
            ],
        ];

        foreach ($registrosAutorizados as $item) {
            $proveedor = strtolower(trim($item['proveedor']));
            $categoria = strtolower(trim($item['categoria']));
            $codigoInterno = strtolower(trim($item['codigo_interno']));
            $codigoExterno = trim((string) $item['codigo_externo']);
            $codigoExternoSecundario = isset($item['codigo_externo_secundario']) && trim((string) $item['codigo_externo_secundario']) !== ''
                ? trim((string) $item['codigo_externo_secundario'])
                : null;

            // Buscar por la clave compuesta lógica de la tabla: (proveedor, categoria, codigo_interno)
            $existente = MapeoCatalogoFacturacion::where('proveedor', $proveedor)
                ->where('categoria', $categoria)
                ->whereRaw('LOWER(TRIM(codigo_interno)) = ?', [$codigoInterno])
                ->first();

            if (!$existente) {
                MapeoCatalogoFacturacion::create([
                    'proveedor' => $proveedor,
                    'categoria' => $categoria,
                    'codigo_interno' => $codigoInterno,
                    'codigo_externo' => $codigoExterno,
                    'codigo_externo_secundario' => $codigoExternoSecundario,
                    'descripcion' => $item['descripcion'],
                    'is_active' => (bool) $item['is_active'],
                ]);

                $this->notifyInfo(
                    "Homologación creada: [{$proveedor} | {$categoria} | {$codigoInterno}] -> {$codigoExterno}" .
                    ($codigoExternoSecundario !== null ? " ({$codigoExternoSecundario})" : '')
                );
                continue;
            }

            $existenteExterno = trim((string) $existente->codigo_externo);
            $existenteSecundario = $existente->codigo_externo_secundario !== null && trim((string) $existente->codigo_externo_secundario) !== ''
                ? trim((string) $existente->codigo_externo_secundario)
                : null;

            // Si existe con códigos externos diferentes, NO sobrescribir e informar el conflicto
            if ($existenteExterno !== $codigoExterno || $existenteSecundario !== $codigoExternoSecundario) {
                $this->notifyWarning(
                    "Conflicto detectado (NO sobrescrito): ya existe [{$proveedor} | {$categoria} | {$existente->codigo_interno}] " .
                    "con codigo_externo='{$existenteExterno}', codigo_externo_secundario='" . ($existenteSecundario ?? 'NULL') . "' " .
                    "(valor del seeder: codigo_externo='{$codigoExterno}', codigo_externo_secundario='" . ($codigoExternoSecundario ?? 'NULL') . "')."
                );
                continue;
            }

            // Si ya existe y coincide con los códigos externos propuestos, se conserva intacto sin duplicar ni sobrescribir
            $this->notifyInfo(
                "Homologación existente conservada sin cambios: [{$proveedor} | {$categoria} | {$existente->codigo_interno}] -> {$existenteExterno}" .
                ($existenteSecundario !== null ? " ({$existenteSecundario})" : '')
            );
        }
    }

    /**
     * Emitir mensaje informativo en consola si el seeder se ejecuta vía Artisan.
     */
    protected function notifyInfo(string $message): void
    {
        if ($this->command) {
            $this->command->info($message);
        }
    }

    /**
     * Emitir advertencia de conflicto en consola si el seeder se ejecuta vía Artisan.
     */
    protected function notifyWarning(string $message): void
    {
        if ($this->command) {
            $this->command->warn($message);
        }
    }
}
