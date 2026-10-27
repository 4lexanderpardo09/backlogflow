<?php

namespace App\Controllers\Projects;

use App\Core\Controller;
use App\Helpers\Delay;
use App\Helpers\Labels;
use App\Helpers\Progress;
use App\Helpers\SprintClose;
use App\Helpers\SprintScope;
use App\Models\Activity;
use App\Models\BacklogItem;
use App\Models\Developer;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\SprintActivity;
use App\Models\SprintCheckin;
use App\Models\SprintClosure;
use App\Services\Projects\SprintReviewService;
use App\Services\Projects\SprintService;

class SprintsController extends Controller
{
    public function indexAction(): void
    {
        $sprintModel = new Sprint();
        $membership = new SprintActivity();
        $closures = (new SprintClosure())->latestBySprint();
        $carried = $membership->carriedCounts();
        $today = date('Y-m-d');

        $sprints = array_map(function (array $s) use ($membership, $closures, $carried, $today) {
            $rows = $membership->forSprint((int) $s['id']);
            $active = array_values(array_filter($rows, fn (array $a) => SprintScope::isActive($a) && $a['status_code'] !== Progress::CANCELLED));
            $scope = SprintScope::summary($rows);
            $open = $s['status'] === 'open';
            // Avance and cumplimiento: live while open, frozen once closed.
            $s['progress'] = $open || $s['completion_percent'] === null
                ? round(Progress::byHours($active), 1)
                : (float) $s['completion_percent'];
            $s['compliance'] = $open || $s['compliance_percent'] === null
                ? $scope['compliance_percent']
                : (float) $s['compliance_percent'];
            // No commitment recorded at the start: the activities heredadas are taken as committed.
            $s['compliance_on_legacy'] = $scope['committed_from_legacy'];
            $delay = Delay::fromActivities($open ? $active : [], $today);
            $s['overdue_count'] = $delay['overdue_count'];
            $s['oldest_open_due'] = $delay['oldest_due'];
            $s['overdue_open'] = SprintClose::isOverdueOpen($s, $today);
            $s['closure'] = $closures[(int) $s['id']] ?? null;
            $s['carried_count'] = $carried[(int) $s['id']] ?? 0;

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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->collectCheckin();
            if ($data === null) {
                $this->flash('error', 'Indica la fecha y cómo va el sprint.');
            } elseif ((new SprintReviewService())->addCheckin($sprintId, $data) !== null) {
                $this->flash('success', 'Seguimiento registrado.');
            } else {
                $this->flash('error', 'El sprint está cerrado: es de solo lectura.');
            }
        }

        $this->redirect('projects/sprints/view/' . $sprintId);
    }

    /** Edits a check-in (week, date, who registered it and the notes). */
    public function editCheckinAction(?string $id): void
    {
        $sprintId = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->collectCheckin();
            if ($data === null) {
                $this->flash('error', 'Indica la fecha y cómo va el sprint.');
                $sprintId = (new SprintCheckin())->find((int) $id)['sprint_id'] ?? null;
            } else {
                $sprintId = (new SprintReviewService())->updateCheckin((int) $id, $data);
                if ($sprintId !== null) {
                    $this->flash('success', 'Seguimiento actualizado.');
                } else {
                    $this->flash('error', 'El sprint está cerrado: es de solo lectura.');
                    $sprintId = (new SprintCheckin())->find((int) $id)['sprint_id'] ?? null;
                }
            }
        }

        $this->redirect($sprintId === null ? 'projects/sprints/index' : 'projects/sprints/view/' . $sprintId);
    }

    public function deleteCheckinAction(?string $id): void
    {
        $checkin = (new SprintCheckin())->find((int) $id);
        $sprint = $checkin === null ? null : (new Sprint())->find((int) $checkin['sprint_id']);

        if ($sprint !== null && !SprintClose::isEditable($sprint)) {
            $this->flash('error', 'El sprint está cerrado: es de solo lectura.');
        } elseif ($checkin !== null) {
            (new SprintCheckin())->delete((int) $id);
            $this->flash('success', 'Seguimiento eliminado.');
        }

        $this->redirect('projects/sprints/view/' . ($checkin['sprint_id'] ?? ''));
    }

    /**
     * JSON: backlog items of one project with their activities, loaded by the
     * sprint form only when the project is opened. ?sprint=<id> is the sprint
     * being edited: an activity in another open sprint can't be picked.
     */
    public function backlogsAction(?string $id): void
    {
        $backlogs = (new BacklogItem())->byProject((int) $id);
        $activities = (new Activity())->byBacklogIds(array_column($backlogs, 'id'));
        $busy = (new SprintActivity())->inOtherOpenSprint(array_column($activities, 'id'), (int) $this->input('sprint', 0));

        $byBacklog = [];
        foreach ($activities as $a) {
            $byBacklog[(int) $a['backlog_item_id']][] = [
                'id' => (int) $a['id'],
                'name' => $a['name'],
                'status' => Labels::get('activity_status', $a['status_code']),
                'done' => in_array($a['status_code'], ['completed', 'cancelled'], true),
                'progress' => (int) $a['progress_percent'],
                'developer' => $a['developer_name'],
                'due' => $a['due_date'],
                'busy' => $busy[(int) $a['id']]['sprint_name'] ?? null,
            ];
        }

        $this->json(array_map(fn (array $b) => [
            'id' => (int) $b['id'],
            'description' => $b['description'],
            'status' => Labels::get('backlog_status', $b['status_code']),
            'done' => in_array($b['status_code'], ['completed', 'cancelled'], true),
            'progress' => (int) round((float) $b['progress_percent']),
            'activities' => $byBacklog[(int) $b['id']] ?? [],
        ], $backlogs));
    }

    public function createAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = (new SprintService())->createSprint($this->collectInput(), $this->projectIds(), $this->activityIds(), $this->changedBy());
            $this->flashSaved('Sprint creado correctamente.', $result['skipped']);
            $this->redirect('projects/sprints/view/' . $result['id']);
            return;
        }

        $this->render('projects/sprints/form', [
            'pageTitle' => 'Nuevo sprint',
            'activeModule' => 'projects-sprints',
            'sprint' => null,
            'selectedProjectIds' => [],
            'selectedActivities' => [],
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

        if (!SprintClose::isEditable($sprint)) {
            $this->flash('error', 'El sprint está cerrado: es de solo lectura. Reábrelo si necesitas cambiarlo.');
            $this->redirect('projects/sprints/view/' . $sprintId);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = (new SprintService())->updateSprint($sprintId, $this->collectInput(), $this->projectIds(), $this->activityIds(), $this->changedBy());
            $this->flashSaved('Sprint actualizado correctamente.', $result['skipped'] ?? []);
            $this->redirect('projects/sprints/view/' . $sprintId);
            return;
        }

        $selected = [];
        foreach ((new SprintActivity())->activeForSprint($sprintId) as $a) {
            $selected[(int) $a['id']] = ['project_id' => (int) $a['project_id'], 'backlog_id' => (int) $a['backlog_item_id']];
        }

        $this->render('projects/sprints/form', [
            'pageTitle' => 'Editar sprint',
            'activeModule' => 'projects-sprints',
            'sprint' => $sprint,
            'selectedProjectIds' => $sprintModel->projectIds($sprintId),
            'selectedActivities' => $selected,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Close wizard. GET shows it; POST closes the sprint with the decision taken
     * for each pending activity and, when some are moved, creates or picks the
     * next sprint for them.
     */
    public function closeAction(?string $id): void
    {
        $sprintId = (int) $id;
        $review = (new SprintReviewService())->review($sprintId);
        if ($review === null) {
            $this->redirect('projects/sprints/index');
            return;
        }
        if (!$review['is_open']) {
            $this->flash('error', 'Este sprint ya está cerrado.');
            $this->redirect('projects/sprints/view/' . $sprintId);
            return;
        }

        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $closedBy = (int) $this->input('closed_by', 0) ?: null;
            $result = $closedBy === null
                ? ['errors' => ['Indica quién cierra el sprint.']]
                : (new SprintService())->closeSprint($sprintId, $this->closeDecisions(), $this->closeTarget(), $closedBy);
            $errors = $result['errors'];

            if ($errors === []) {
                $nextId = $result['next_sprint_id'];
                $this->flash('success', $nextId === null
                    ? 'Sprint cerrado. Se guardó la foto del cierre.'
                    : 'Sprint cerrado. Las actividades movidas ya están en este sprint, marcadas como «Arrastrada desde».');
                $this->redirect('projects/sprints/view/' . ($nextId ?? $sprintId));
                return;
            }
        }

        $sprint = $review['sprint'];
        $today = date('Y-m-d');
        $rows = array_merge([], ...array_map(fn (array $p) => array_merge([], ...array_column($p['items'], 'activities')), $review['projects']));

        $this->render('projects/sprints/close', [
            'pageTitle' => 'Cerrar ' . ($sprint['name'] ?: 'Sprint #' . $sprint['id']),
            'activeModule' => 'projects-sprints',
            'review' => $review,
            'finished' => array_values(array_filter($rows, fn (array $a) => SprintClose::isFinished($a['status_code']))),
            'pending' => array_values(array_filter($rows, fn (array $a) => !SprintClose::isFinished($a['status_code']) && $a['status_code'] !== Progress::CANCELLED)),
            'cancelledBefore' => array_values(array_filter($rows, fn (array $a) => $a['status_code'] === Progress::CANCELLED)),
            'openSprints' => (new Sprint())->openExcept($sprintId),
            'suggested' => SprintClose::suggestedDates($today, $sprint['start_date'], $sprint['end_date'])
                + ['name' => SprintClose::suggestedName($sprint['name'])],
            'developers' => (new Developer())->allForList(),
            'errors' => $errors,
            'posted' => $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : null,
        ]);
    }

    /** Reopens a closed sprint; the reason is mandatory and the close snapshot is kept. */
    public function reopenAction(?string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (new SprintService())->reopenSprint((int) $id, (string) $this->input('reason', ''))) {
            $this->flash('success', 'Sprint reabierto. La foto del cierre anterior se conserva en el historial.');
        } else {
            $this->flash('error', 'Escribe el motivo para reabrir el sprint.');
        }
        $this->redirect('projects/sprints/view/' . (int) $id);
    }

    public function deleteAction(?string $id): void
    {
        if ((new SprintService())->deleteSprint((int) $id)) {
            $this->flash('success', 'Sprint eliminado.');
        } else {
            $this->flash('error', 'Un sprint cerrado no se puede eliminar: es parte del historial.');
        }
        $this->redirect('projects/sprints/index');
    }

    /** @return array<int, array{action:string, reason:string}> activity id => decision from the wizard */
    private function closeDecisions(): array
    {
        $decisions = [];
        foreach ((array) ($_POST['decision'] ?? []) as $activityId => $action) {
            $decisions[(int) $activityId] = [
                'action' => (string) $action,
                'reason' => trim((string) ($_POST['reason'][$activityId] ?? '')),
            ];
        }

        return $decisions;
    }

    /** Next sprint for the moved activities: an existing open one or a new one with the suggested (edited) data. */
    private function closeTarget(): ?array
    {
        if ($this->input('target_mode') === 'existing') {
            $id = (int) $this->input('target_id', 0);

            return $id > 0 ? ['mode' => 'existing', 'id' => $id] : null;
        }

        $start = $this->dateInput('next_start_date');
        $end = $this->dateInput('next_end_date');
        if ($start === null || $end === null || $end < $start) {
            return null;
        }

        return ['mode' => 'new', 'data' => [
            'name' => trim((string) $this->input('next_name', '')) ?: null,
            'goal' => trim((string) $this->input('next_goal', '')) ?: null,
            'start_date' => $start,
            'end_date' => $end,
            'process_owner' => null,
            'notes' => null,
        ]];
    }

    private function flashSaved(string $message, array $skipped): void
    {
        if ($skipped === []) {
            $this->flash('success', $message);
        } else {
            $this->flash('error', $message . ' No se agregaron porque ya están en otro sprint abierto: ' . implode('; ', $skipped) . '.');
        }
    }

    private function changedBy(): ?int
    {
        return (int) $this->input('changed_by', 0) ?: null;
    }

    /** Null when the date or the summary is missing. */
    private function collectCheckin(): ?array
    {
        $summary = trim((string) $this->input('summary', ''));
        $date = $this->dateInput('checkin_date');

        if ($summary === '' || $date === null) {
            return null;
        }

        return [
            'checkin_date' => $date,
            'week_number' => (int) $this->input('week_number', 0) ?: null,
            'registered_by' => (int) $this->input('registered_by', 0) ?: null,
            'summary' => $summary,
            'blockers' => trim((string) $this->input('blockers', '')) ?: null,
            'next_steps' => trim((string) $this->input('next_steps', '')) ?: null,
        ];
    }

    private function collectInput(): array
    {
        $start = $this->dateInput('start_date') ?? date('Y-m-d');

        return [
            'name' => trim((string) $this->input('name')) ?: null,
            'goal' => trim((string) $this->input('goal', '')) ?: null,
            'start_date' => $start,
            'end_date' => $this->dateInput('end_date') ?? date('Y-m-d', strtotime($start . ' +14 days')),
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
    private function activityIds(): array
    {
        return array_values(array_filter(array_map('intval', (array) ($_POST['activity_ids'] ?? [])), fn (int $i) => $i > 0));
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
            'developers' => (new Developer())->allForList(),
        ];
    }
}
