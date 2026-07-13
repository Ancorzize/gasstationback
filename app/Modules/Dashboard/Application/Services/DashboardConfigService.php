<?php

namespace App\Modules\Dashboard\Application\Services;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Dashboard\Application\DTOs\GuardarDashboardRolDTO;
use App\Modules\Dashboard\Application\Interfaces\DashboardConfigRepositoryInterface;

class DashboardConfigService
{
    public function __construct(
        protected DashboardConfigRepositoryInterface $repository
    ) {}

    public function getRoles()
    {
        return $this->repository
            ->getRoles();
    }

    public function getConfiguracionRol(
        int $roleId
    ) {

        $role = $this->repository
            ->findRoleById($roleId);

        if (!$role) {

            throw new HttpException(
                404,
                'El rol no existe.'
            );

        }

        return [

            'role' => [

                'id' => $role->id,

                'name' => $role->name,

            ],

            'widgets' => $this->repository
                ->getWidgetsByRole($roleId),

        ];
    }

    public function guardarConfiguracion(
        GuardarDashboardRolDTO $dto
    ): bool {

        return DB::transaction(function () use ($dto) {

            $role = $this->repository
                ->findRoleById($dto->role_id);

            if (!$role) {

                throw new HttpException(
                    404,
                    'El rol no existe.'
                );

            }

            $this->repository
                ->deleteWidgetsByRole(
                    $dto->role_id
                );

            foreach ($dto->widgets as $widget) {

                $this->repository
                    ->createWidgetRole([

                        'role_id' => $dto->role_id,

                        'dashboard_widget_id' => $widget['widget_id'],

                        'visible' => $widget['visible'],

                        'orden' => $widget['orden'],

                    ]);

            }

            return true;

        });

    }
}