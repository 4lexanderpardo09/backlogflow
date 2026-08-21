<?php

namespace App\Controllers\Projects;

use App\Core\Controller;
use App\Helpers\ActivityStatus;
use App\Helpers\DateMath;
use App\Models\Activity;
use App\Models\BacklogItem;
use App\Models\Catalog;
use App\Models\Developer;
use App\Models\Project;

class ActivitiesController extends Controller
{
    public function indexAction(): void
    {
        $developerFilter = (int) $this->input('developer_id', 0);
        $projectFilter = (int) $this->input('project_id', 0);
        $today = date('Y-m-d');
        $activities = (new Activity())->allWithDetails();

        if ($developerFilter > 0) {
            $activities = array_values(array_filter(
                $activities,
                fn (array $a) => (int) $a['developer_id'] === $developerFilter
                    || in_array((string) $developerFilter, explode(',', (string) ($a['collaborator_ids'] ?? '')), true)
            ));
        }

        if ($projectFilter > 0) {
            $activities = array_values(array_filter(
                $activities,
                fn (array $a) => (int) $a['project_id'] === $projectFilter
            ));
        }

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
            'developerFilter' => $developerFilter,
            'projectFilter' => $projectFilter,
            'projects' => (new Project())->all('name ASC'),
            ...$this->formOptions(null),
        ]);
    }

    public function createAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $model = new Activity();
            $activityId = $model->insert($this->collectInput());
            $model->syncDevelopers($activityId, $this->collaboratorIds());
            $this->flash('success', 'Actividad creada correctamente.');
            $this->redirect('projects/activities/index');
            return;
        }

        $this->render('projects/activities/form', [
            'pageTitle' => 'Nueva actividad',
            'activeModule' => 'projects-activities',
            'activity' => null,
            'collaborators' => [],
            ...$this->formOptions(null),
        ]);
    }

    public function editAction(?string $id): void
    {
        $activityId = (int) $id;
        $model = new Activity();
        $activity = $model->find($activityId);

        if ($activity === null) {
            $this->redirect('projects/activities/index');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $model->update($activityId, $this->collectInput());
            $model->syncDevelopers($activityId, $this->collaboratorIds());
            $this->flash('success', 'Actividad actualizada correctamente. El avance del backlog y del proyecto se recalculó automáticamente.');
            $this->redirect('projects/activities/index');
            return;
        }

        $this->render('projects/activities/form', [
            'pageTitle' => 'Editar actividad',
            'activeModule' => 'projects-activities',
            'activity' => $activity,
            'collaborators' => $model->additionalDevelopers($activityId),
            ...$this->formOptions($activityId),
        ]);
    }

    public function deleteAction(?string $id): void
    {
        (new Activity())->delete((int) $id);
        $this->flash('success', 'Actividad eliminada.');
        $this->redirect('projects/activities/index');
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
