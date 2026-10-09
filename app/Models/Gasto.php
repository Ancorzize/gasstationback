<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Gasto extends Model
{
    protected $table = 'gastos';

    protected $fillable = [
        'fecha_gasto',
        'proveedor_id',
        'categoria_gasto_id',
        'caja_id',
        'user_id',
        'medio_pago',
        'valor',
        'descripcion',
        'estado',
        'motivo_anulacion',
        'user_anulacion_id',
        'fecha_anulacion',
        'documento_soporte_electronico',
        'estado_documento_soporte',
    ];

    protected $casts = [
        'fecha_gasto' => 'date',
        'valor' => 'decimal:2',
        'fecha_anulacion' => 'datetime',
        'documento_soporte_electronico' => 'boolean',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function categoriaGasto(): BelongsTo
    {
        return $this->belongsTo(CategoriaGasto::class, 'categoria_gasto_id');
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function usuarioAnulacion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_anulacion_id');
    }

    public function documentoElectronico(): HasOne
    {
        return $this->hasOne(DocumentoElectronico::class, 'gasto_id');
    }

    public function documentosElectronicos(): HasMany
    {
        return $this->hasMany(DocumentoElectronico::class, 'gasto_id');
    }
}