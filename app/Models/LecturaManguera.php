<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LecturaManguera extends Model
{
    protected $table = 'lecturas_mangueras';

    protected $fillable = [
        'turno_islero_id',
        'manguera_id',
        'lectura_inicial',
        'lectura_final',
        'galones_vendidos',
        'precio_galon',
        'total_venta',
    ];

    protected $casts = [
        'lectura_inicial' => 'decimal:3',
        'lectura_final' => 'decimal:3',
        'galones_vendidos' => 'decimal:3',
        'precio_galon' => 'decimal:2',
        'total_venta' => 'decimal:2',
    ];

    public function turno(): BelongsTo
    {
        return $this->belongsTo(TurnoIslero::class, 'turno_islero_id');
    }

    public function manguera(): BelongsTo
    {
        return $this->belongsTo(Manguera::class);
    }
}