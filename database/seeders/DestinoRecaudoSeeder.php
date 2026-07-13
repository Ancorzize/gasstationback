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
                'codigo' => 'COMB',
                'nombre' => 'Combustible',
                'descripcion' => 'Ventas de combustible',
                'is_active' => true,
            ],
            [
                'id' => 2,
                'codigo' => 'LUBR',
                'nombre' => 'Lubricantes',
                'descripcion' => 'Lubricantes',
                'is_active' => true,
            ]
        ]);
    }
}