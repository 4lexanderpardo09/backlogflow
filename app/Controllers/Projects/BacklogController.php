<?php

namespace App\Controllers\Projects;

use App\Core\Controller;
use App\Models\Activity;
use App\Models\BacklogItem;
use App\Models\Catalog;
use App\Models\Developer;
use App\Models\IdeaNote;
use App\Models\Project;
use App\Models\Sprint;

class BacklogController extends Controller
{
    public function indexAction(): void
    {
        $developerFilter = (int) $this->input('developer_id', 0);
        $projectFilter = (int) $this->input('project_id', 0);
        $backlogItems = (new BacklogItem())->allWithDetails();

        if ($developerFilter > 0) {
            $backlogItems = array_values(array_filter(
                $backlogItems,
                fn (array $b) => (int) $b['developer_id'] === $developerFilter
                    || in_array((string) $developerFilter, explode(',', (string) ($b['collaborator_ids'] ?? '')), true)
            ));
        }

        if ($projectFilter > 0) {
            $backlogItems = array_values(array_filter(
                $backlogItems,
                fn (array $b) => (int) $b['project_id'] === $projectFilter
            ));
        }

        $this->render('projects/backlog/index', [
            'pageTitle' => 'Backlog',
            'activeModule' => 'projects-backlog',
            'backlogItems' => $backlogItems,
            'developerFilter' => $developerFilter,
            'projectFilter' => $projectFilter,
            ...$this->formOptions(),
        ]);
    }

    public function createAction(): void
    {
        $fromNoteId = (int) $this->input('from_note', 0);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->collectInput();
            $data['created_date'] = $data['created_date'] ?: date('Y-m-d');
            $model = new BacklogItem();
            $backlogId = $model->insert($data);
            $model->syncDevelopers($backlogId, $this->collaboratorIds());

            if ($fromNoteId > 0) {
                (new IdeaNote())->markConverted($fromNoteId, $backlogId);
            }

            $this->flash('success', 'Elemento de backlog creado correctamente.');
            $this->redirect('projects/backlog/index');
            return;
        }

        $prefill = null;
        if ($fromNoteId > 0) {
            $note = (new IdeaNote())->find($fromNoteId);
            if ($note !== null) {
                $prefill = ['project_id' => $note['project_id'], 'description' => mb_substr($note['text'], 0, 255)];
            }
        }

        $this->render('projects/backlog/form', [
            'pageTitle' => 'Nuevo elemento de backlog',
            'activeModule' => 'projects-backlog',
            'backlogItem' => $prefill,
            'collaborators' => [],
            'fromNoteId' => $fromNoteId,
            ...$this->formOptions(),
        ]);
    }

    public function editAction(?string $id): void
    {
        $model = new BacklogItem();
        $item = $model->find((int) $id);

        if ($item === null) {
            $this->redirect('projects/backlog/index');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $model->update((int) $id, $this->collectInput());
            $model->syncDevelopers((int) $id, $this->collaboratorIds());
            $this->flash('success', 'Elemento de backlog actualizado correctamente.');
            $this->redirect('projects/backlog/index');
            return;
        }

        $this->render('projects/backlog/form', [
            'pageTitle' => 'Editar elemento de backlog',
            'activeModule' => 'projects-backlog',
            'backlogItem' => $item,
            'collaborators' => $model->additionalDevelopers((int) $id),
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

    /** @return int[] developer_id values from the "colaboradores adicionales" multi-select, excluding the primary developer. */
    private function collaboratorIds(): array
    {
        $primary = (int) $this->input('developer_id');
        $ids = array_map('intval', (array) ($_POST['collaborator_ids'] ?? []));

        return array_values(array_filter($ids, fn (int $id) => $id > 0 && $id !== $primary));
    }

    private function collectInput(): array
    {
        return [
            'project_id' => (int) $this->input('project_id'),
            'sprint_id' => $this->input('sprint_id') ?: null,
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
            'sprints' => (new Sprint())->allOpenWithProject(),
            'developers' => (new Developer())->all('name ASC'),
            'types' => (new Catalog('cat_backlog_types'))->all(),
            'priorities' => (new Catalog('cat_priorities'))->all(),
            'statuses' => (new Catalog('cat_backlog_statuses'))->all(),
        ];
    }
}
