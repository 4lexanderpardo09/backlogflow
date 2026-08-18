<?php

namespace App\Controllers\Sla;

use App\Core\Controller;
use App\Helpers\SlaCompliance;
use App\Models\Application;

class IndicatorsController extends Controller
{
    public function indexAction(): void
    {
        $applicationModel = new Application();
        $latest = $applicationModel->latestIndicatorPerApplication();
        $applications = array_column($applicationModel->allWithDetails(), null, 'id');

        $rows = array_map(function (array $indicator) use ($applications) {
            $indicator['application_name'] = $applications[$indicator['application_id']]['name'] ?? 'Por definir';
            $indicator['semaphore'] = SlaCompliance::semaphore(
                (float) $indicator['response_compliance_pct'],
                (float) $indicator['resolution_compliance_pct'],
                (int) $indicator['breach_count']
            );

            return $indicator;
        }, $latest);

        $this->render('sla/indicators/index', [
            'pageTitle' => 'Indicadores de cumplimiento',
            'activeModule' => 'sla-indicators',
            'rows' => $rows,
        ]);
    }

    public function createAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pdo = \App\Core\Database::connection();
            $pdo->prepare(
                'INSERT INTO sla_monthly_indicators (application_id, month, response_compliance_pct, resolution_compliance_pct,
                    availability_pct, incident_count, critical_incident_count, recurring_incident_count,
                    avg_response_time, avg_resolution_time, breach_count, escalation_count)
                 VALUES (:application_id, :month, :response_compliance_pct, :resolution_compliance_pct,
                    :availability_pct, :incident_count, :critical_incident_count, :recurring_incident_count,
                    :avg_response_time, :avg_resolution_time, :breach_count, :escalation_count)
                 ON DUPLICATE KEY UPDATE response_compliance_pct = VALUES(response_compliance_pct),
                    resolution_compliance_pct = VALUES(resolution_compliance_pct), availability_pct = VALUES(availability_pct),
                    incident_count = VALUES(incident_count), critical_incident_count = VALUES(critical_incident_count),
                    recurring_incident_count = VALUES(recurring_incident_count), avg_response_time = VALUES(avg_response_time),
                    avg_resolution_time = VALUES(avg_resolution_time), breach_count = VALUES(breach_count),
                    escalation_count = VALUES(escalation_count)'
            )->execute([
                'application_id' => (int) $this->input('application_id'),
                'month' => $this->input('month'),
                'response_compliance_pct' => $this->input('response_compliance_pct', 0),
                'resolution_compliance_pct' => $this->input('resolution_compliance_pct', 0),
                'availability_pct' => $this->input('availability_pct', 0),
                'incident_count' => $this->input('incident_count', 0),
                'critical_incident_count' => $this->input('critical_incident_count', 0),
                'recurring_incident_count' => $this->input('recurring_incident_count', 0),
                'avg_response_time' => $this->input('avg_response_time') ?: null,
                'avg_resolution_time' => $this->input('avg_resolution_time') ?: null,
                'breach_count' => $this->input('breach_count', 0),
                'escalation_count' => $this->input('escalation_count', 0),
            ]);

            $this->flash('success', 'Indicador mensual registrado correctamente.');
            $this->redirect('sla/indicators/index');
            return;
        }

        $this->render('sla/indicators/form', [
            'pageTitle' => 'Registrar indicador mensual',
            'activeModule' => 'sla-indicators',
            'applications' => (new Application())->all('name ASC'),
        ]);
    }
}
