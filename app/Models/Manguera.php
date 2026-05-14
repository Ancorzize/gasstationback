<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Manguera extends Model
{
    protected $table = 'mangueras';

    protected $fillable = [
        'bomba_id',
        'producto_id',
        'nombre',
        'codigo',
        'is_active',
    ];

    protected $casts = [
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

    public function turnosIslero(): BelongsToMany
    {
        return $this->belongsToMany(
            TurnoIslero::class,
            'turno_islero_mangueras',
            'manguera_id',
            'turno_islero_id'
        )->withTimestamps();
    }
}