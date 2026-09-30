<?php

namespace App\Controllers\Projects;

use App\Core\Controller;
use App\Helpers\Labels;
use App\Helpers\SupportLog;
use App\Models\Developer;
use App\Models\SupportLogEntry;

class SupportLogController extends Controller
{
    public function indexAction(): void
    {
        $filters = [
            'developer_id' => (int) $this->input('developer_id', 0),
            'category' => (string) $this->input('category', ''),
            'helpdesk' => (string) $this->input('helpdesk', ''),
            'desde' => $this->dateInput('desde'),
            'hasta' => $this->dateInput('hasta'),
        ];
        $entries = (new SupportLogEntry())->filtered($filters);

        $this->render('projects/support-log/index', [
            'pageTitle' => 'Soporte diario',
            'activeModule' => 'support-log',
            'entries' => $entries,
            'summary' => SupportLog::summarize($entries),
            'filters' => $filters,
            'developers' => (new Developer())->allForList(),
        ]);
    }

    public function createAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->collectInput();
            if ($data === null) {
                $this->flash('error', 'Completa la fecha, quién lo atendió y la descripción.');
            } else {
                (new SupportLogEntry())->insert($data);
                $this->flash('success', 'Soporte registrado correctamente.');
            }
        }

        $this->redirect('projects/support-log/index');
    }

    public function editAction(?string $id): void
    {
        $model = new SupportLogEntry();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $model->find((int) $id) !== null) {
            $data = $this->collectInput();
            if ($data === null) {
                $this->flash('error', 'Completa la fecha, quién lo atendió y la descripción.');
            } else {
                $model->update((int) $id, $data);
                $this->flash('success', 'Registro actualizado correctamente.');
            }
        }

        $this->redirect('projects/support-log/index');
    }

    public function deleteAction(?string $id): void
    {
        (new SupportLogEntry())->delete((int) $id);
        $this->flash('success', 'Registro eliminado.');
        $this->redirect('projects/support-log/index');
    }

    /** Null when a required field is missing or invalid. */
    private function collectInput(): ?array
    {
        $date = $this->dateInput('log_date');
        $developerId = (int) $this->input('developer_id', 0);
        $description = trim((string) $this->input('description', ''));
        $category = (string) $this->input('category', 'other');
        $inHelpdesk = $this->input('in_helpdesk') === '1' ? 1 : 0;

        if ($date === null || $developerId <= 0 || $description === '' || (new Developer())->find($developerId) === null) {
            return null;
        }

        return [
            'log_date' => $date,
            'developer_id' => $developerId,
            'requester' => trim((string) $this->input('requester', '')) ?: null,
            'requester_area' => trim((string) $this->input('requester_area', '')) ?: null,
            'category' => array_key_exists($category, Labels::options('support_category')) ? $category : 'other',
            'description' => $description,
            'in_helpdesk' => $inHelpdesk,
            'helpdesk_ticket' => $inHelpdesk === 1 ? (trim((string) $this->input('helpdesk_ticket', '')) ?: null) : null,
            'time_minutes' => max(0, (int) $this->input('time_minutes', 0)),
        ];
    }
}
