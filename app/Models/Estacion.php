<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estacion extends Model
{
    protected $table = 'estaciones';

    protected $fillable = [
        'nombre',
        'codigo',
        'direccion',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function bombas(): HasMany
    {
        return $this->hasMany(Bomba::class);
    }

    public function turnosIslero(): HasMany
    {
        return $this->hasMany(TurnoIslero::class);
    }
}