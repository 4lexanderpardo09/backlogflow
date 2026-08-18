<?php

namespace App\Controllers\Projects;

use App\Core\Controller;
use App\Models\Catalog;
use App\Models\Developer;
use App\Services\Projects\DashboardService;

class DashboardController extends Controller
{
    public function indexAction(): void
    {
        $filters = $this->filtersFromRequest();
        $summary = (new DashboardService())->summary($filters);

        $this->render('projects/dashboard/index', [
            'pageTitle' => 'Dashboard de Proyectos',
            'activeModule' => 'projects-dashboard',
            'summary' => $summary,
            'filters' => $filters,
            'developers' => (new Developer())->all('name ASC'),
            'priorities' => (new Catalog('cat_priorities'))->all(),
            'statuses' => (new Catalog('cat_project_statuses'))->all(),
        ]);
    }

    /** JSON endpoint the dashboard could poll for a live refresh without a full reload. */
    public function dataAction(): void
    {
        $this->json((new DashboardService())->summary($this->filtersFromRequest()));
    }

    private function filtersFromRequest(): array
    {
        return [
            'developer_id' => $this->input('developer_id') ?: '',
            'priority' => $this->input('priority') ?: '',
            'status' => $this->input('status') ?: '',
        ];
    }
}
