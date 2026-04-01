<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pais extends Model
{
    protected $table = 'paises';

    protected $fillable = [
        'nombre',
        'codigo_iso',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function departamentos(): HasMany
    {
        return $this->hasMany(Departamento::class);
    }
}