<?php

namespace Database\Seeders;

use App\Models\Pais;
use App\Models\Ciudad;
use App\Models\Departamento;
use Illuminate\Database\Seeder;

class UbicacionSeeder extends Seeder
{
    public function run(): void
    {
        $colombia = Pais::firstOrCreate(
            ['nombre' => 'Colombia'],
            [
                'codigo_iso' => 'CO',
                'is_active' => true,
            ]
        );

        $antioquia = Departamento::firstOrCreate(
            [
                'pais_id' => $colombia->id,
                'nombre' => 'Antioquia',
            ],
            [
                'codigo' => '05',
                'is_active' => true,
            ]
        );

        $cundinamarca = Departamento::firstOrCreate(
            [
                'pais_id' => $colombia->id,
                'nombre' => 'Cundinamarca',
            ],
            [
                'codigo' => '25',
                'is_active' => true,
            ]
        );

        $valle = Departamento::firstOrCreate(
            [
                'pais_id' => $colombia->id,
                'nombre' => 'Valle del Cauca',
            ],
            [
                'codigo' => '76',
                'is_active' => true,
            ]
        );

        $huila = Departamento::firstOrCreate(
            [
                'pais_id' => $colombia->id,
                'nombre' => 'Huila',
            ],
            [
                'codigo' => '41',
                'is_active' => true,
            ]
        );

        $ciudades = [
            [
                'departamento_id' => $antioquia->id,
                'nombre' => 'Medellín',
                'codigo' => '05001',
            ],
            [
                'departamento_id' => $antioquia->id,
                'nombre' => 'Envigado',
                'codigo' => '05266',
            ],
            [
                'departamento_id' => $cundinamarca->id,
                'nombre' => 'Bogotá',
                'codigo' => '11001',
            ],
            [
                'departamento_id' => $valle->id,
                'nombre' => 'Cali',
                'codigo' => '76001',
            ],
            [
                'departamento_id' => $huila->id,
                'nombre' => 'La Plata',
                'codigo' => '41396',
            ],
            
            [
                'departamento_id' => $huila->id,
                'nombre' => 'Neiva',
                'codigo' => '41001',
            ],
            [
                'departamento_id' => $huila->id,
                'nombre' => 'Pitalito',
                'codigo' => '41551',
            ],
            [
                'departamento_id' => $huila->id,
                'nombre' => 'Garzón',
                'codigo' => '41298',
            ],
            [
                'departamento_id' => $huila->id,
                'nombre' => 'San Agustín',
                'codigo' => '41668',
            ],
            [
                'departamento_id' => $huila->id,
                'nombre' => 'Campoalegre',
                'codigo' => '41132',
            ],
            [
                'departamento_id' => $huila->id,
                'nombre' => 'Gigante',
                'codigo' => '41306',
            ],
            [
                'departamento_id' => $huila->id,
                'nombre' => 'Isnos',
                'codigo' => '41359',
            ],
            [
                'departamento_id' => $huila->id,
                'nombre' => 'Aipe',
                'codigo' => '41016',
            ],
            [
                'departamento_id' => $huila->id,
                'nombre' => 'Rivera',
                'codigo' => '41615',
            ],
            [
                'departamento_id' => $huila->id,
                'nombre' => 'Palermo',
                'codigo' => '41524',
            ],
        ];

        foreach ($ciudades as $ciudad) {
            Ciudad::firstOrCreate(
                [
                    'departamento_id' => $ciudad['departamento_id'],
                    'nombre' => $ciudad['nombre'],
                ],
                [
                    'codigo' => $ciudad['codigo'],
                    'is_active' => true,
                ]
            );
        }
    }
}