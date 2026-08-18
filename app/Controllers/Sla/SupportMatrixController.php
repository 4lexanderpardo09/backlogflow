<?php

namespace App\Controllers\Sla;

use App\Core\Controller;
use App\Models\Application;

class SupportMatrixController extends Controller
{
    public function indexAction(): void
    {
        $applicationModel = new Application();
        $applications = $applicationModel->allWithDetails();

        $rows = array_map(fn (array $app) => [
            'application' => $app,
            'levels' => $applicationModel->supportMatrix((int) $app['id']),
        ], $applications);

        $this->render('sla/support-matrix/index', [
            'pageTitle' => 'Matriz de soporte',
            'activeModule' => 'sla-supportmatrix',
            'rows' => $rows,
        ]);
    }

    public function editAction(?string $id, array $params): void
    {
        $applicationId = (int) $id;
        $level = (int) ($params['level'] ?? $this->input('level', 1));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pdo = \App\Core\Database::connection();
            $data = [
                'application_id' => $applicationId,
                'level' => $level,
                'responsible' => $this->input('responsible') ?: null,
                'channel' => $this->input('channel') ?: null,
                'hours' => $this->input('hours') ?: null,
                'max_escalation_time' => $this->input('max_escalation_time') ?: null,
                'contact' => $this->input('contact') ?: null,
                'email' => $this->input('email') ?: null,
                'phone' => $this->input('phone') ?: null,
                'procedure_notes' => $this->input('procedure_notes') ?: null,
            ];

            $pdo->prepare(
                'INSERT INTO support_matrix (application_id, level, responsible, channel, hours, max_escalation_time, contact, email, phone, procedure_notes)
                 VALUES (:application_id, :level, :responsible, :channel, :hours, :max_escalation_time, :contact, :email, :phone, :procedure_notes)
                 ON DUPLICATE KEY UPDATE responsible = VALUES(responsible), channel = VALUES(channel), hours = VALUES(hours),
                    max_escalation_time = VALUES(max_escalation_time), contact = VALUES(contact), email = VALUES(email),
                    phone = VALUES(phone), procedure_notes = VALUES(procedure_notes)'
            )->execute($data);

            $this->flash('success', 'Nivel de soporte guardado correctamente.');
            $this->redirect('sla/support-matrix/index');
            return;
        }

        $applicationModel = new Application();
        $application = $applicationModel->find($applicationId);
        $existing = array_values(array_filter(
            $applicationModel->supportMatrix($applicationId),
            fn ($row) => (int) $row['level'] === $level
        ))[0] ?? null;

        $this->render('sla/support-matrix/form', [
            'pageTitle' => 'Editar nivel de soporte',
            'activeModule' => 'sla-supportmatrix',
            'application' => $application,
            'level' => $level,
            'entry' => $existing,
        ]);
    }
}
