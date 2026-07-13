<?php

namespace App\Modules\Dashboard\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use App\Models\DashboardWidget;
use App\Models\DashboardWidgetRole;
use App\Modules\Dashboard\Application\Interfaces\DashboardConfigRepositoryInterface;

class DashboardConfigRepository implements DashboardConfigRepositoryInterface
{
    public function getRoles(): Collection
    {
        return Role::query()

            ->orderBy('name')

            ->get();
    }

    public function getWidgets(): Collection
    {
        return DashboardWidget::query()

            ->where('is_active', true)

            ->orderBy('nombre')

            ->get();
    }

    public function getWidgetsByRole(
        int $roleId
    ): Collection {

        return DashboardWidget::query()

            ->leftJoin(
                'dashboard_widget_roles',
                function ($join) use ($roleId) {

                    $join->on(
                        'dashboard_widgets.id',
                        '=',
                        'dashboard_widget_roles.dashboard_widget_id'
                    )

                    ->where(
                        'dashboard_widget_roles.role_id',
                        $roleId
                    );

                }
            )

            ->select([

                'dashboard_widgets.id',

                'dashboard_widgets.codigo',

                'dashboard_widgets.nombre',

                'dashboard_widgets.tipo',

                'dashboard_widgets.is_active',

                'dashboard_widget_roles.visible',

                'dashboard_widget_roles.orden',

            ])

            ->where(
                'dashboard_widgets.is_active',
                true
            )

            ->orderBy(
                'dashboard_widgets.nombre'
            )

            ->get();
    }

    public function deleteWidgetsByRole(
        int $roleId
    ): void {

        DashboardWidgetRole::query()

            ->where(
                'role_id',
                $roleId
            )

            ->delete();
    }

    public function createWidgetRole(
        array $data
    ): void {

        DashboardWidgetRole::create($data);

    }

    public function findRoleById(
        int $roleId
    ): ?Role {

        return Role::find($roleId);

    }
}