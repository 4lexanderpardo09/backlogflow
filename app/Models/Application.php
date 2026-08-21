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

    /**
     * Support types this application offers, each with its level (1|2) and
     * escalation/contact info for that specific type — this is the "matriz
     * de soporte" of the application: which types it provides and who to
     * contact/escalate to for each one.
     */
    public function supportTypes(int $applicationId): array
    {
        return $this->fetchAll(
            'SELECT st.id AS support_type_id, st.code, st.name, ast.level, ast.responsible, ast.channel, ast.hours,
                    ast.max_escalation_time, ast.contact, ast.email, ast.phone, ast.procedure_notes
             FROM application_support_type ast
             JOIN cat_support_types st ON st.id = ast.support_type_id
             WHERE ast.application_id = :id
             ORDER BY ast.level ASC, st.name ASC',
            ['id' => $applicationId]
        );
    }

    /**
     * Replaces the full set of support types assigned to an application.
     *
     * @param array<int,array{level?:int,responsible?:?string,channel?:?string,hours?:?string,
     *     max_escalation_time?:?string,contact?:?string,email?:?string,phone?:?string,procedure_notes?:?string}> $dataByTypeId
     *     support_type_id => fields, only for the types the app offers
     */
    public function saveSupportTypes(int $applicationId, array $dataByTypeId): void
    {
        $this->db->prepare('DELETE FROM application_support_type WHERE application_id = :id')->execute(['id' => $applicationId]);

        $stmt = $this->db->prepare(
            'INSERT INTO application_support_type
                (application_id, support_type_id, level, responsible, channel, hours, max_escalation_time, contact, email, phone, procedure_notes)
             VALUES
                (:application_id, :support_type_id, :level, :responsible, :channel, :hours, :max_escalation_time, :contact, :email, :phone, :procedure_notes)'
        );
        foreach ($dataByTypeId as $typeId => $data) {
            $level = (int) ($data['level'] ?? 2);
            $level = in_array($level, [1, 2], true) ? $level : 2;
            $stmt->execute([
                'application_id' => $applicationId,
                'support_type_id' => (int) $typeId,
                'level' => $level,
                'responsible' => $data['responsible'] ?? null,
                'channel' => $data['channel'] ?? null,
                'hours' => $data['hours'] ?? null,
                'max_escalation_time' => $data['max_escalation_time'] ?? null,
                'contact' => $data['contact'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'procedure_notes' => $data['procedure_notes'] ?? null,
            ]);
        }
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
