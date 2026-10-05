<?php

namespace App\Controllers\Projects;

use App\Core\Controller;
use App\Helpers\Labels;
use App\Models\BacklogItem;
use App\Models\Developer;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\SprintCheckin;
use App\Services\Projects\SprintReviewService;
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
        $review = (new SprintReviewService())->review((int) $id);

        if ($review === null) {
            $this->redirect('projects/sprints/index');
            return;
        }

        $sprint = $review['sprint'];

        $this->render('projects/sprints/view', [
            'pageTitle' => ($sprint['name'] ?: 'Sprint') . ' #' . $sprint['id'],
            'activeModule' => 'projects-sprints',
            'review' => $review,
            'developers' => (new Developer())->allForList(),
        ]);
    }

    /** Registers a weekly follow-up (any day of the sprint) with a snapshot of its progress. */
    public function checkinAction(?string $id): void
    {
        $sprintId = (int) $id;
        $summary = trim((string) $this->input('summary', ''));
        $date = $this->dateInput('checkin_date');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($summary === '' || $date === null) {
                $this->flash('error', 'Indica la fecha y cómo va el sprint.');
            } elseif ((new SprintReviewService())->addCheckin($sprintId, [
                'checkin_date' => $date,
                'registered_by' => (int) $this->input('registered_by', 0) ?: null,
                'summary' => $summary,
                'blockers' => trim((string) $this->input('blockers', '')) ?: null,
                'next_steps' => trim((string) $this->input('next_steps', '')) ?: null,
            ]) !== null) {
                $this->flash('success', 'Seguimiento registrado.');
            }
        }

        $this->redirect('projects/sprints/view/' . $sprintId);
    }

    public function deleteCheckinAction(?string $id): void
    {
        $checkin = (new SprintCheckin())->find((int) $id);

        if ($checkin !== null) {
            (new SprintCheckin())->delete((int) $id);
            $this->flash('success', 'Seguimiento eliminado.');
        }

        $this->redirect('projects/sprints/view/' . ($checkin['sprint_id'] ?? ''));
    }

    /** JSON: backlog items of one project, loaded by the sprint form only when the project is opened. */
    public function backlogsAction(?string $id): void
    {
        $this->json(array_map(fn (array $b) => [
            'id' => (int) $b['id'],
            'description' => $b['description'],
            'status' => Labels::get('backlog_status', $b['status_code']),
            'done' => in_array($b['status_code'], ['completed', 'cancelled'], true),
            'progress' => (int) round((float) $b['progress_percent']),
        ], (new BacklogItem())->byProject((int) $id)));
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
            'selectedBacklogs' => [],
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
            'selectedBacklogs' => $sprintModel->backlogProjects($sprintId),
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
        $all = (new Project())->all('name ASC');
        $platformNames = array_column($all, 'name', 'id');

        // Sub-projects and standalone projects can be part of a sprint; a platform row cannot.
        // Ordered by platform so the picker can group a platform's modules together.
        $selectable = array_values(array_filter($all, fn (array $p) => (int) $p['is_platform'] !== 1));
        usort($selectable, fn (array $a, array $b) => [$a['parent_id'] === null ? 1 : 0, $platformNames[$a['parent_id']] ?? '', $a['name']]
            <=> [$b['parent_id'] === null ? 1 : 0, $platformNames[$b['parent_id']] ?? '', $b['name']]);

        return [
            'allProjects' => $all,
            'projects' => $selectable,
            'backlogCounts' => (new BacklogItem())->countsByProject(),
        ];
    }
}
