<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoriaProducto extends Model
{
    protected $table = 'categorias_producto';

    protected $fillable = [
        'nombre',
        'descripcion',
        'is_active',
        'destino_recaudo_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function destinoRecaudo(): BelongsTo
    {
        return $this->belongsTo(
            DestinoRecaudo::class,
            'destino_recaudo_id'
        );
    }
}