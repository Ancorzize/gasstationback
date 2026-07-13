<?php

namespace App\Modules\Dashboard\Application\Services;

use App\Models\User;
use App\Modules\Dashboard\Application\Interfaces\DashboardRepositoryInterface;

class DashboardService
{
    public function __construct(

        protected DashboardRepositoryInterface $dashboardRepository,

        protected DashboardIndicatorService $indicatorService,

    ) {}

    public function getDashboard(User $user, ?string $fechaDesde, ?string $fechaHasta): array
    {
        $role = $user->roles->first();
        $fechaDesde ??= now()
            ->startOfMonth()
            ->toDateString();

        $fechaHasta ??= now()
            ->toDateString();

        if (!$role) {

            return [
                'widgets' => []
            ];

        }

        $widgets =
            $this->dashboardRepository
                ->getWidgetsByRole(
                    $role->id
                );

        $resultado = [];

        $this->indicatorService->setPeriodo($fechaDesde,$fechaHasta);

        foreach ($widgets as $item) {

            $widget = $item->widget;

            $resultado[] = [

                'codigo' => $widget->codigo,

                'nombre' => $widget->nombre,

                'tipo' => $widget->tipo,

                'categoria' => $widget->categoria,

                'icono' => $widget->icono,

                'color' => $widget->color,

                'ancho' => $widget->ancho,

                'alto' => $widget->alto,

                'orden' => $item->orden,

                'data' => $this->indicatorService
                    ->getIndicator(
                        $widget->codigo
                    ),

            ];

        }

        return [

            'widgets' => $resultado

        ];
    }
}