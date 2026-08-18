<?php

namespace App\Controllers\Sla;

use App\Core\Controller;
use App\Models\Application;
use App\Models\Catalog;
use App\Services\Sla\DashboardService;

class DashboardController extends Controller
{
    public function indexAction(): void
    {
        $filters = $this->filtersFromRequest();
        $summary = (new DashboardService())->summary($filters);
        $applications = (new Application())->allWithDetails();

        $this->render('sla/dashboard/index', [
            'pageTitle' => 'Dashboard de ANS',
            'activeModule' => 'sla-dashboard',
            'summary' => $summary,
            'filters' => $filters,
            'criticalityLevels' => (new Catalog('cat_criticality_levels'))->all(),
            'types' => (new Catalog('cat_application_types'))->all(),
            'providerNames' => array_values(array_unique(array_filter(array_column($applications, 'provider_name')))),
        ]);
    }

    public function dataAction(): void
    {
        $this->json((new DashboardService())->summary($this->filtersFromRequest()));
    }

    private function filtersFromRequest(): array
    {
        return [
            'criticality' => $this->input('criticality') ?: '',
            'type' => $this->input('type') ?: '',
            'provider' => $this->input('provider') ?: '',
        ];
    }
}
