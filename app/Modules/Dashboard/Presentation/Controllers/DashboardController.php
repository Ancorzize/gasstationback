<?php

namespace App\Modules\Dashboard\Presentation\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Shared\Responses\ApiResponse;
use App\Modules\Dashboard\Application\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index(
        Request $request
    )
    {

        return ApiResponse::success(

             $this->dashboardService->getDashboard(
                    $request->user(),
                    $request->get('fecha_desde'),
                    $request->get('fecha_hasta')
                ),
            'Dashboard.'

        );
    }
}