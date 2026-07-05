<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DestinoRecaudo;

class DestinoRecaudoSeeder extends Seeder
{
    public function run(): void
    {
        DestinoRecaudo::insert([
            [
                'id' => 1,
                'codigo' => '001',
                'nombre' => 'Combustible',
                'descripcion' => 'Ventas de combustible',
                'is_active' => true,
            ],
            [
                'id' => 2,
                'codigo' => '002',
                'nombre' => 'Lubricantes',
                'descripcion' => 'Lubricantes y servicios',
                'is_active' => true,
            ],-
            [
                'id' => 3,
                'codigo' => '003',
                'nombre' => 'Cartera',
                'descripcion' => 'Abonos de cartera',
                'is_active' => true,
            ],
        ]);
    }
}