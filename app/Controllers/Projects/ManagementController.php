<?php

namespace App\Controllers\Projects;

use App\Core\Controller;
use App\Services\Projects\DashboardService;

/**
 * "Vista de seguimiento gerencial": the consolidated
 * Project / Developer / Progress / Priority / Status / Due date / Traffic-light
 * table required by spec section 9. Reuses DashboardService so the numbers
 * always match the dashboard.
 */
class ManagementController extends Controller
{
    public function indexAction(): void
    {
        $summary = (new DashboardService())->summary();

        $this->render('projects/management/index', [
            'pageTitle' => 'Seguimiento gerencial',
            'activeModule' => 'projects-management',
            'projects' => $summary['projects'],
        ]);
    }
}
