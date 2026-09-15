<?php

namespace App\Controllers\Projects;

use App\Core\Controller;
use App\Helpers\DateMath;
use App\Helpers\ProjectScope;
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
        $filters = [
            'developer_id' => (int) $this->input('developer_id', 0),
            'project_id' => (int) $this->input('project_id', 0),
            'child_id' => (int) $this->input('child_id', 0),
            'priority' => (string) $this->input('priority', ''),
            'status' => (string) $this->input('status', ''),
            'desde' => $this->dateInput('desde'),
            'hasta' => $this->dateInput('hasta'),
        ];
        $projects = (new Project())->all('name ASC');
        $scopeIds = ProjectScope::ids($projects, $filters['project_id'], $filters['child_id']);
        $backlogItems = (new BacklogItem())->allWithDetails();

        if ($filters['developer_id'] > 0) {
            $developerFilter = $filters['developer_id'];
            $backlogItems = array_values(array_filter(
                $backlogItems,
                fn (array $b) => (int) $b['developer_id'] === $developerFilter
                    || in_array((string) $developerFilter, explode(',', (string) ($b['collaborator_ids'] ?? '')), true)
            ));
        }

        // A backlog item's dates run from its creation to its target date.
        $backlogItems = array_values(array_filter($backlogItems, fn (array $b) =>
            ($scopeIds === null || in_array((int) $b['project_id'], $scopeIds, true))
            && ($filters['priority'] === '' || $b['priority_code'] === $filters['priority'])
            && ($filters['status'] === '' || $b['status_code'] === $filters['status'])
            && DateMath::rangeOverlaps($b['created_date'], $b['target_date'], $filters['desde'], $filters['hasta'])
        ));

        $this->render('projects/backlog/index', [
            'pageTitle' => 'Backlog',
            'activeModule' => 'projects-backlog',
            'backlogItems' => $backlogItems,
            'filters' => $filters,
            'topProjects' => ProjectScope::topLevel($projects),
            'childProjects' => ProjectScope::childrenOf($projects, $filters['project_id']),
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

        // Backlog › [platform ›] project: the way back to both the list and the project.
        $project = (new Project())->findWithDetails((int) $item['project_id']);
        $breadcrumbs = [['label' => 'Backlog', 'route' => 'projects/backlog/index']];
        if (!empty($project['parent_id'])) {
            $breadcrumbs[] = ['label' => $project['parent_name'], 'route' => 'projects/projects/view/' . (int) $project['parent_id']];
        }
        if ($project !== null) {
            $breadcrumbs[] = ['label' => $project['name'], 'route' => 'projects/projects/view/' . (int) $project['id']];
        }

        $this->render('projects/backlog/view', [
            'pageTitle' => $item['description'],
            'activeModule' => 'projects-backlog',
            'breadcrumbs' => $breadcrumbs,
            'backlogItem' => $item,
            'activities' => (new Activity())->byBacklog($backlogId),
            'sprints' => (new Sprint())->forBacklog($backlogId),
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
