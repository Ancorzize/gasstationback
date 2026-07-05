<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DestinoRecaudo extends Model
{
    protected $table = 'destinos_recaudo';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function categorias(): HasMany
    {
        return $this->hasMany(
            CategoriaProducto::class,
            'destino_recaudo_id'
        );
    }

    public function cajas(): HasMany
    {
        return $this->hasMany(
            Caja::class,
            'destino_recaudo_id'
        );
    }
}