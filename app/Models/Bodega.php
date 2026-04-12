<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bodega extends Model
{
    protected $table = 'bodegas';

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'direccion',
        'telefono',
        'responsable_id',
        'tipo_bodega',
        'is_principal',
        'is_active',
    ];

    protected $casts = [
        'is_principal' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'bodega_id');
    }
}