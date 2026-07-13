<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DashboardWidget extends Model
{
    protected $table = 'dashboard_widgets';

    protected $fillable = [

        'codigo',

        'nombre',

        'tipo',

        'categoria',

        'icono',

        'color',

        'ancho',

        'alto',

        'descripcion',

        'is_active',

    ];

    protected $casts = [

        'is_active' => 'boolean',

    ];

    public function roles(): HasMany
    {
        return $this->hasMany(
            DashboardWidgetRole::class
        );
    }
}