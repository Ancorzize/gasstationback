<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class DashboardWidgetRole extends Model
{
    protected $table = 'dashboard_widget_roles';

    protected $fillable = [

        'dashboard_widget_id',

        'role_id',

        'orden',

        'visible',

    ];

    protected $casts = [

        'visible' => 'boolean',

    ];

    public function widget(): BelongsTo
    {
        return $this->belongsTo(
            DashboardWidget::class,
            'dashboard_widget_id'
        );
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(
            Role::class
        );
    }
}