<?php

namespace App\Services\Sla;

use App\Helpers\ContractAlert;
use App\Helpers\TrafficLight;
use App\Models\Application;
use App\Models\ContractExpiration;

/**
 * Builds every aggregate the SLA management dashboard needs (spec section
 * 17): counts by ownership/modality/support, contract alert buckets, and
 * average compliance/availability from the latest reported month per app.
 */
class DashboardService
{
    public function __construct(
        private readonly Application $applicationModel = new Application(),
        private readonly ContractExpiration $contractModel = new ContractExpiration(),
    ) {
    }

    /**
     * @param array{criticality?: string, type?: string, provider?: string} $filters
     *        Narrows the application set first; contracts and compliance
     *        indicators are then narrowed to only those applications so the
     *        whole dashboard reacts, not just the application list.
     */
    public function summary(array $filters = [], ?string $today = null): array
    {
        $today ??= date('Y-m-d');

        $applications = $this->applicationModel->allWithOwnership();

        if (!empty($filters['criticality'])) {
            $applications = array_values(array_filter($applications, fn ($a) => $a['criticality_code'] === $filters['criticality']));
        }
        if (!empty($filters['type'])) {
            $applications = array_values(array_filter($applications, fn ($a) => $a['type_code'] === $filters['type']));
        }
        if (!empty($filters['provider'])) {
            $applications = array_values(array_filter($applications, fn ($a) => $a['provider_name'] === $filters['provider']));
        }

        $applicationIds = array_map('intval', array_column($applications, 'id'));
        $isFiltered = array_filter($filters) !== [];

        $contracts = $this->contractModel->allWithApplication();
        $latestIndicators = $this->applicationModel->latestIndicatorPerApplication();

        if ($isFiltered) {
            $contracts = array_values(array_filter($contracts, fn ($c) => in_array((int) $c['application_id'], $applicationIds, true)));
            $latestIndicators = array_values(array_filter($latestIndicators, fn ($i) => in_array((int) $i['application_id'], $applicationIds, true)));
        }

        $contracts = array_map(function (array $c) use ($today) {
            $c['bucket'] = ContractAlert::bucket($c['expiration_date'], $today);
            return $c;
        }, $contracts);

        $upcomingContracts = array_values(array_filter(
            $contracts,
            fn ($c) => in_array($c['bucket'], [ContractAlert::EXPIRED, ContractAlert::LT_30, ContractAlert::D30_60], true)
        ));

        $appIdsWithAns = array_unique(array_column($latestIndicators, 'application_id'));
        $appsWithBreaches = array_values(array_filter($latestIndicators, fn ($i) => (int) $i['breach_count'] > 0));

        $avgAvailability = $latestIndicators === [] ? null
            : round(array_sum(array_column($latestIndicators, 'availability_pct')) / count($latestIndicators), 1);
        $avgCompliance = $latestIndicators === [] ? null
            : round(array_sum(array_map(
                fn ($i) => ((float) $i['response_compliance_pct'] + (float) $i['resolution_compliance_pct']) / 2,
                $latestIndicators
            )) / count($latestIndicators), 1);

        return [
            'today' => $today,
            'kpi' => [
                'total_applications' => count($applications),
                'critical_applications' => count(array_filter($applications, fn ($a) => $a['criticality_code'] === 'critical')),
                'in_house_applications' => count(array_filter($applications, fn ($a) => $a['ownership'] === 'in_house')),
                'third_party_applications' => count(array_filter($applications, fn ($a) => $a['ownership'] === 'outsourced')),
                'saas_applications' => count(array_filter($applications, fn ($a) => $a['modality_code'] === 'saas')),
                'providers_count' => count(array_unique(array_filter(array_column($applications, 'provider_name')))),
                'contracts_expiring_soon' => count($upcomingContracts),
                'applications_without_ans' => count($applications) - count($appIdsWithAns),
                'applications_with_ans' => count($appIdsWithAns),
                'applications_with_breaches' => count(array_unique(array_column($appsWithBreaches, 'application_id'))),
                'avg_availability' => $avgAvailability,
                'avg_compliance' => $avgCompliance,
            ],
            'applications' => $applications,
            'upcoming_contracts' => $upcomingContracts,
            'criticality_distribution' => $this->countBy($applications, 'criticality_code'),
            'type_distribution' => $this->countBy($applications, 'type_code'),
            'provider_distribution' => $this->countBy($applications, 'provider_name'),
            'modality_distribution' => $this->countBy($applications, 'modality_code'),
        ];
    }

    private function countBy(array $rows, string $column): array
    {
        $counts = [];
        foreach ($rows as $row) {
            $key = $row[$column] ?? 'Por definir';
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return $counts;
    }
}
