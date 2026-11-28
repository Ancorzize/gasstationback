<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    protected string $guard_name = 'sanctum';

    use HasApiTokens, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'bodega_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'qr_login_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(Bodega::class, 'bodega_id');
    }

    public function turnosIslero(): HasMany
    {
        return $this->hasMany(TurnoIslero::class);
    }

    /**
     * Relación opcional con el registro de Empleado.
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    /**
     * Genera un token único y compacto para el login por QR.
     */
    public static function generateUniqueQrToken(): string
    {
        do {
            $token = 'GSQR:' . Str::random(32);
        } while (static::where('qr_login_token', $token)->exists());

        return $token;
    }

    /**
     * Devuelve el token QR actual del usuario o lo genera automáticamente si aún no existe.
     */
    public function ensureQrLoginToken(): string
    {
        if (empty($this->qr_login_token)) {
            return $this->regenerateQrLoginToken();
        }

        return $this->qr_login_token;
    }

    /**
     * Regenera el token QR del usuario, invalidando de inmediato cualquier QR impreso anterior.
     */
    public function regenerateQrLoginToken(): string
    {
        $token = static::generateUniqueQrToken();
        $this->forceFill(['qr_login_token' => $token])->save();

        return $token;
    }
}