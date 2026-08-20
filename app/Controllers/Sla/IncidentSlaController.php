<?php

namespace App\Controllers\Sla;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Application;

/**
 * CRUD for the per-application "ANS de incidentes por prioridad" table
 * (sla_incidents: response/resolution time targets by priority, spec
 * sections 12-13). Rows with application_id NULL are the global defaults
 * an application falls back to until it gets its own override here.
 */
class IncidentSlaController extends Controller
{
    public function editAction(?string $id, array $params): void
    {
        $applicationId = (int) $id;
        $priority = trim((string) ($params['priority'] ?? $this->input('priority', '')));

        $applicationModel = new Application();
        $application = $applicationModel->find($applicationId);

        if ($application === null) {
            $this->redirect('sla/applications/index');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $priority = trim((string) $this->input('priority'));
            $data = [
                'application_id' => $applicationId,
                'priority' => $priority,
                'description' => $this->input('description') ?: null,
                'response_time_minutes' => (int) $this->input('response_time_minutes', 0),
                'resolution_time_minutes' => (int) $this->input('resolution_time_minutes', 0),
            ];

            $pdo = Database::connection();
            $existingId = $this->input('row_id') ?: null;

            if (!$existingId) {
                $existing = $pdo->prepare(
                    'SELECT id FROM sla_incidents WHERE application_id = :application_id AND priority = :priority'
                );
                $existing->execute(['application_id' => $applicationId, 'priority' => $priority]);
                $existingId = $existing->fetchColumn() ?: null;
            }

            if ($existingId) {
                $pdo->prepare(
                    'UPDATE sla_incidents SET priority = :priority, description = :description,
                        response_time_minutes = :response_time_minutes, resolution_time_minutes = :resolution_time_minutes
                     WHERE id = :id AND application_id = :application_id'
                )->execute([...$data, 'id' => $existingId]);
            } else {
                $pdo->prepare(
                    'INSERT INTO sla_incidents (application_id, priority, description, response_time_minutes, resolution_time_minutes)
                     VALUES (:application_id, :priority, :description, :response_time_minutes, :resolution_time_minutes)'
                )->execute($data);
            }

            $this->flash('success', 'Tiempos de respuesta y solución guardados correctamente.');
            $this->redirect('sla/applications/view/' . $applicationId);
            return;
        }

        $existing = null;
        if ($priority !== '') {
            $rows = $applicationModel->incidentSla($applicationId);
            foreach ($rows as $row) {
                if ($row['priority'] === $priority) {
                    $existing = $row;
                    break;
                }
            }
        }

        $this->render('sla/applications/incident-sla-form', [
            'pageTitle' => 'Editar tiempos de respuesta',
            'activeModule' => 'sla-applications',
            'application' => $application,
            'priority' => $priority,
            'entry' => $existing,
        ]);
    }

    public function deleteAction(?string $id): void
    {
        $pdo = Database::connection();
        $row = $pdo->prepare('SELECT application_id FROM sla_incidents WHERE id = :id AND application_id IS NOT NULL');
        $row->execute(['id' => (int) $id]);
        $applicationId = $row->fetchColumn();

        if ($applicationId === false) {
            $this->flash('error', 'No se puede eliminar un valor por defecto global.');
            $this->redirect('sla/applications/index');
            return;
        }

        $pdo->prepare('DELETE FROM sla_incidents WHERE id = :id')->execute(['id' => (int) $id]);
        $this->flash('success', 'Prioridad eliminada de la ficha de la aplicación.');
        $this->redirect('sla/applications/view/' . $applicationId);
    }
}
