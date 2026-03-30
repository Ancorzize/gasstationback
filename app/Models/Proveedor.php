<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';

   protected $fillable = [
        'nombre',
        'nit',
        'telefono',
        'direccion',
        'email',
        'is_active',
    ];
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}