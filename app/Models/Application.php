<?php

namespace App\Models;

use App\Core\Model;

class Application extends Model
{
    protected string $table = 'applications';

    public function allWithDetails(): array
    {
        return $this->fetchAll(
            'SELECT a.*, at.code AS type_code, cl.code AS criticality_code,
                    pr.name AS provider_name, ap.contract_expiration_date
             FROM applications a
             JOIN cat_application_types at ON at.id = a.application_type_id
             JOIN cat_criticality_levels cl ON cl.id = a.criticality_id
             LEFT JOIN application_provider ap ON ap.application_id = a.id
             LEFT JOIN providers pr ON pr.id = ap.provider_id
             ORDER BY a.name ASC'
        );
    }

    public function findWithDetails(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT a.*, at.code AS type_code, cl.code AS criticality_code,
                    pr.id AS provider_id, pr.name AS provider_name,
                    ap.contract_number, ap.contract_start_date, ap.contract_expiration_date
             FROM applications a
             JOIN cat_application_types at ON at.id = a.application_type_id
             JOIN cat_criticality_levels cl ON cl.id = a.criticality_id
             LEFT JOIN application_provider ap ON ap.application_id = a.id
             LEFT JOIN providers pr ON pr.id = ap.provider_id
             WHERE a.id = :id',
            ['id' => $id]
        );
    }

    /** Same as allWithDetails() plus ownership/modality, for dashboard aggregates. */
    public function allWithOwnership(): array
    {
        return $this->fetchAll(
            'SELECT a.*, at.code AS type_code, cl.code AS criticality_code,
                    pr.name AS provider_name, ap.contract_expiration_date,
                    ao.ownership, mo.code AS modality_code
             FROM applications a
             JOIN cat_application_types at ON at.id = a.application_type_id
             JOIN cat_criticality_levels cl ON cl.id = a.criticality_id
             LEFT JOIN application_provider ap ON ap.application_id = a.id
             LEFT JOIN providers pr ON pr.id = ap.provider_id
             LEFT JOIN application_ownership ao ON ao.application_id = a.id
             LEFT JOIN cat_modalities mo ON mo.id = ao.modality_id
             ORDER BY a.name ASC'
        );
    }

    public function ownership(int $applicationId): ?array
    {
        return $this->fetchOne(
            'SELECT ao.*, mo.code AS modality_code
             FROM application_ownership ao
             LEFT JOIN cat_modalities mo ON mo.id = ao.modality_id
             WHERE ao.application_id = :id',
            ['id' => $applicationId]
        );
    }

    public function schedule(int $applicationId): ?array
    {
        return $this->fetchOne('SELECT * FROM application_schedule WHERE application_id = :id', ['id' => $applicationId]);
    }

    public function availability(int $applicationId): ?array
    {
        return $this->fetchOne('SELECT * FROM application_availability WHERE application_id = :id', ['id' => $applicationId]);
    }

    public function supportMatrix(int $applicationId): array
    {
        return $this->fetchAll('SELECT * FROM support_matrix WHERE application_id = :id ORDER BY level ASC', ['id' => $applicationId]);
    }

    public function supportTypes(int $applicationId): array
    {
        return $this->fetchAll(
            'SELECT st.code FROM application_support_type ast
             JOIN cat_support_types st ON st.id = ast.support_type_id
             WHERE ast.application_id = :id',
            ['id' => $applicationId]
        );
    }

    public function integrations(int $applicationId): array
    {
        return $this->fetchAll('SELECT * FROM application_integrations WHERE application_id = :id', ['id' => $applicationId]);
    }

    public function dependencies(int $applicationId): array
    {
        return $this->fetchAll('SELECT * FROM application_dependencies WHERE application_id = :id', ['id' => $applicationId]);
    }

    public function raci(int $applicationId): array
    {
        return $this->fetchAll('SELECT * FROM raci_matrix WHERE application_id = :id', ['id' => $applicationId]);
    }

    public function incidentSla(int $applicationId): array
    {
        return $this->fetchAll(
            "SELECT * FROM sla_incidents WHERE application_id = :id
             UNION ALL
             SELECT * FROM sla_incidents WHERE application_id IS NULL
               AND NOT EXISTS (SELECT 1 FROM sla_incidents WHERE application_id = :id2)
             ORDER BY priority ASC",
            ['id' => $applicationId, 'id2' => $applicationId]
        );
    }

    public function monthlyIndicators(int $applicationId): array
    {
        return $this->fetchAll(
            'SELECT * FROM sla_monthly_indicators WHERE application_id = :id ORDER BY month DESC',
            ['id' => $applicationId]
        );
    }

    /** Most recent sla_monthly_indicators row per application, for the dashboard compliance KPIs. */
    public function latestIndicatorPerApplication(): array
    {
        return $this->fetchAll(
            'SELECT i.*
             FROM sla_monthly_indicators i
             INNER JOIN (
                 SELECT application_id, MAX(month) AS max_month
                 FROM sla_monthly_indicators
                 GROUP BY application_id
             ) latest ON latest.application_id = i.application_id AND latest.max_month = i.month'
        );
    }
}
