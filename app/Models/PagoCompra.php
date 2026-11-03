<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PagoCompra extends Model
{
    protected $table = 'pagos_compra';

    protected $fillable = [
        'compra_id',
        'user_id',
        'fecha_pago',
        'monto',
        'metodo_pago',
        'observacion',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto' => 'decimal:2',
    ];

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function anticipo(): HasOne
    {
        return $this->hasOne(AnticipoProveedor::class, 'pago_compra_id');
    }
}
