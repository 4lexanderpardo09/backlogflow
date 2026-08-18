<?php

namespace App\Controllers\Projects;

use App\Core\Controller;
use App\Helpers\ActivityStatus;
use App\Helpers\DateMath;
use App\Models\Activity;
use App\Models\BacklogItem;
use App\Models\Catalog;
use App\Models\Developer;

class ActivitiesController extends Controller
{
    public function indexAction(): void
    {
        $today = date('Y-m-d');
        $activities = (new Activity())->allWithDetails();

        $activities = array_map(function (array $a) use ($today) {
            $hasUnresolvedDependency = $a['depends_on_activity_id'] !== null
                && (int) ($a['depends_on_progress'] ?? 0) < 100;

            $a['system_status'] = ActivityStatus::compute((int) $a['progress_percent'], $a['due_date'], $hasUnresolvedDependency, $today);
            $a['days_remaining'] = DateMath::daysRemaining($a['due_date'], $today);

            return $a;
        }, $activities);

        $this->render('projects/activities/index', [
            'pageTitle' => 'Actividades',
            'activeModule' => 'projects-activities',
            'activities' => $activities,
            ...$this->formOptions(null),
        ]);
    }

    public function createAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new Activity())->insert($this->collectInput());
            $this->flash('success', 'Actividad creada correctamente.');
            $this->redirect('projects/activities/index');
            return;
        }

        $this->render('projects/activities/form', [
            'pageTitle' => 'Nueva actividad',
            'activeModule' => 'projects-activities',
            'activity' => null,
            ...$this->formOptions(null),
        ]);
    }

    public function editAction(?string $id): void
    {
        $activityId = (int) $id;
        $activity = (new Activity())->find($activityId);

        if ($activity === null) {
            $this->redirect('projects/activities/index');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new Activity())->update($activityId, $this->collectInput());
            $this->flash('success', 'Actividad actualizada correctamente. El avance del backlog y del proyecto se recalculó automáticamente.');
            $this->redirect('projects/activities/index');
            return;
        }

        $this->render('projects/activities/form', [
            'pageTitle' => 'Editar actividad',
            'activeModule' => 'projects-activities',
            'activity' => $activity,
            ...$this->formOptions($activityId),
        ]);
    }

    public function deleteAction(?string $id): void
    {
        (new Activity())->delete((int) $id);
        $this->flash('success', 'Actividad eliminada.');
        $this->redirect('projects/activities/index');
    }

    private function collectInput(): array
    {
        return [
            'backlog_item_id' => (int) $this->input('backlog_item_id'),
            'developer_id' => (int) $this->input('developer_id'),
            'name' => trim((string) $this->input('name')),
            'description' => $this->input('description') ?: null,
            'type_id' => $this->input('type_id') ?: null,
            'priority_id' => (int) $this->input('priority_id'),
            'status_id' => (int) $this->input('status_id'),
            'start_date' => $this->input('start_date') ?: null,
            'due_date' => $this->input('due_date') ?: null,
            'end_date' => $this->input('end_date') ?: null,
            'progress_percent' => max(0, min(100, (int) $this->input('progress_percent', 0))),
            'depends_on_activity_id' => $this->input('depends_on_activity_id') ?: null,
            'notes' => $this->input('notes') ?: null,
        ];
    }

    private function formOptions(?int $currentActivityId): array
    {
        return [
            'backlogItems' => (new BacklogItem())->all('description ASC'),
            'developers' => (new Developer())->all('name ASC'),
            'types' => (new Catalog('cat_activity_types'))->all(),
            'priorities' => (new Catalog('cat_priorities'))->all(),
            'statuses' => (new Catalog('cat_activity_statuses'))->all(),
            'dependencyOptions' => (new Activity())->selectableDependencies($currentActivityId),
        ];
    }
}
