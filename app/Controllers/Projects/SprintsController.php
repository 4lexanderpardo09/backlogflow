<?php

namespace App\Controllers\Projects;

use App\Core\Controller;
use App\Models\BacklogItem;
use App\Models\Project;
use App\Models\Sprint;
use App\Services\Projects\SprintService;

class SprintsController extends Controller
{
    public function indexAction(): void
    {
        $sprintModel = new Sprint();

        $sprints = array_map(function (array $s) use ($sprintModel) {
            $s['live_percent'] = $sprintModel->completionFor((int) $s['id']);

            return $s;
        }, $sprintModel->allWithMeta());

        $this->render('projects/sprints/index', [
            'pageTitle' => 'Sprints',
            'activeModule' => 'projects-sprints',
            'sprints' => $sprints,
        ]);
    }

    public function viewAction(?string $id): void
    {
        $sprintId = (int) $id;
        $sprintModel = new Sprint();
        $sprint = $sprintModel->find($sprintId);

        if ($sprint === null) {
            $this->redirect('projects/sprints/index');
            return;
        }

        $items = $sprintModel->backlogs($sprintId);

        $this->render('projects/sprints/view', [
            'pageTitle' => ($sprint['name'] ?: 'Sprint') . ' #' . $sprint['id'],
            'activeModule' => 'projects-sprints',
            'sprint' => $sprint,
            'projects' => $sprintModel->projects($sprintId),
            'items' => $items,
            'livePercent' => $sprintModel->completionFor($sprintId),
        ]);
    }

    public function createAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new SprintService())->createSprint($this->collectInput(), $this->projectIds(), $this->backlogIds());
            $this->flash('success', 'Sprint creado correctamente.');
            $this->redirect('projects/sprints/index');
            return;
        }

        $this->render('projects/sprints/form', [
            'pageTitle' => 'Nuevo sprint',
            'activeModule' => 'projects-sprints',
            'sprint' => null,
            'selectedProjectIds' => [],
            'selectedBacklogIds' => [],
            ...$this->formOptions(),
        ]);
    }

    public function editAction(?string $id): void
    {
        $sprintId = (int) $id;
        $sprintModel = new Sprint();
        $sprint = $sprintModel->find($sprintId);

        if ($sprint === null) {
            $this->redirect('projects/sprints/index');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new SprintService())->updateSprint($sprintId, $this->collectInput(), $this->projectIds(), $this->backlogIds());
            $this->flash('success', 'Sprint actualizado correctamente.');
            $this->redirect('projects/sprints/index');
            return;
        }

        $this->render('projects/sprints/form', [
            'pageTitle' => 'Editar sprint',
            'activeModule' => 'projects-sprints',
            'sprint' => $sprint,
            'selectedProjectIds' => $sprintModel->projectIds($sprintId),
            'selectedBacklogIds' => $sprintModel->backlogIds($sprintId),
            ...$this->formOptions(),
        ]);
    }

    /** Closes a sprint: freezes its % de cumplimiento (activity-weighted). */
    public function closeAction(?string $id): void
    {
        (new SprintService())->closeSprint((int) $id);
        $this->flash('success', 'Sprint cerrado. El % de cumplimiento quedó registrado.');
        $this->redirect('projects/sprints/index');
    }

    public function reopenAction(?string $id): void
    {
        (new SprintService())->reopenSprint((int) $id);
        $this->flash('success', 'Sprint reabierto.');
        $this->redirect('projects/sprints/index');
    }

    public function deleteAction(?string $id): void
    {
        (new Sprint())->delete((int) $id);
        $this->flash('success', 'Sprint eliminado.');
        $this->redirect('projects/sprints/index');
    }

    private function collectInput(): array
    {
        return [
            'name' => trim((string) $this->input('name')) ?: null,
            'start_date' => $this->input('start_date') ?: date('Y-m-d'),
            'duration_weeks' => max(1, (int) $this->input('duration_weeks', 2)),
            'process_owner' => $this->input('process_owner') ?: null,
            'notes' => $this->input('notes') ?: null,
        ];
    }

    /** @return int[] */
    private function projectIds(): array
    {
        return array_values(array_filter(array_map('intval', (array) ($_POST['project_ids'] ?? [])), fn (int $i) => $i > 0));
    }

    /** @return int[] */
    private function backlogIds(): array
    {
        return array_values(array_filter(array_map('intval', (array) ($_POST['backlog_ids'] ?? [])), fn (int $i) => $i > 0));
    }

    private function formOptions(): array
    {
        return [
            // Sub-projects and standalone projects can be part of a sprint; a platform row cannot.
            'projects' => array_values(array_filter(
                (new Project())->all('name ASC'),
                fn (array $p) => (int) $p['is_platform'] !== 1
            )),
            'backlogItems' => (new BacklogItem())->allWithDetails(),
        ];
    }
}
