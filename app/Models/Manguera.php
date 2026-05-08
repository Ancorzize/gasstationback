<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manguera extends Model
{
    protected $table = 'mangueras';

    protected $fillable = [
        'bomba_id',
        'producto_id',
        'nombre',
        'codigo',
        'precio_actual',
        'is_active',
    ];

    protected $casts = [
        'precio_actual' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function bomba(): BelongsTo
    {
        return $this->belongsTo(Bomba::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(LecturaManguera::class);
    }
}