<?php

namespace App\Controllers\Projects;

use App\Core\Controller;
use App\Models\Activity;
use App\Models\BacklogItem;
use App\Models\Catalog;
use App\Models\Developer;
use App\Models\Project;

class BacklogController extends Controller
{
    public function indexAction(): void
    {
        $this->render('projects/backlog/index', [
            'pageTitle' => 'Backlog',
            'activeModule' => 'projects-backlog',
            'backlogItems' => (new BacklogItem())->allWithDetails(),
            ...$this->formOptions(),
        ]);
    }

    public function createAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->collectInput();
            $data['created_date'] = $data['created_date'] ?: date('Y-m-d');
            (new BacklogItem())->insert($data);
            $this->flash('success', 'Elemento de backlog creado correctamente.');
            $this->redirect('projects/backlog/index');
            return;
        }

        $this->render('projects/backlog/form', [
            'pageTitle' => 'Nuevo elemento de backlog',
            'activeModule' => 'projects-backlog',
            'backlogItem' => null,
            ...$this->formOptions(),
        ]);
    }

    public function editAction(?string $id): void
    {
        $item = (new BacklogItem())->find((int) $id);

        if ($item === null) {
            $this->redirect('projects/backlog/index');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new BacklogItem())->update((int) $id, $this->collectInput());
            $this->flash('success', 'Elemento de backlog actualizado correctamente.');
            $this->redirect('projects/backlog/index');
            return;
        }

        $this->render('projects/backlog/form', [
            'pageTitle' => 'Editar elemento de backlog',
            'activeModule' => 'projects-backlog',
            'backlogItem' => $item,
            ...$this->formOptions(),
        ]);
    }

    public function viewAction(?string $id): void
    {
        $backlogId = (int) $id;
        $item = (new BacklogItem())->findWithDetails($backlogId);

        if ($item === null) {
            $this->redirect('projects/backlog/index');
            return;
        }

        $this->render('projects/backlog/view', [
            'pageTitle' => $item['description'],
            'activeModule' => 'projects-backlog',
            'backlogItem' => $item,
            'activities' => (new Activity())->byBacklog($backlogId),
        ]);
    }

    public function deleteAction(?string $id): void
    {
        $model = new BacklogItem();
        $hasActivities = count((new Activity())->byBacklog((int) $id)) > 0;

        if ($hasActivities) {
            $this->flash('error', 'No se puede eliminar: este backlog tiene actividades asociadas.');
        } else {
            $model->delete((int) $id);
            $this->flash('success', 'Elemento de backlog eliminado.');
        }

        $this->redirect('projects/backlog/index');
    }

    private function collectInput(): array
    {
        return [
            'project_id' => (int) $this->input('project_id'),
            'developer_id' => (int) $this->input('developer_id'),
            'description' => trim((string) $this->input('description')),
            'type_id' => $this->input('type_id') ?: null,
            'priority_id' => (int) $this->input('priority_id'),
            'status_id' => (int) $this->input('status_id'),
            'created_date' => $this->input('created_date') ?: null,
            'target_date' => $this->input('target_date') ?: null,
            'notes' => $this->input('notes') ?: null,
        ];
    }

    private function formOptions(): array
    {
        return [
            'projects' => (new Project())->all('name ASC'),
            'developers' => (new Developer())->all('name ASC'),
            'types' => (new Catalog('cat_backlog_types'))->all(),
            'priorities' => (new Catalog('cat_priorities'))->all(),
            'statuses' => (new Catalog('cat_backlog_statuses'))->all(),
        ];
    }
}
