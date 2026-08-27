<?php

namespace App\Controllers\Projects;

use App\Core\Controller;
use App\Helpers\DateMath;
use App\Helpers\Progress;
use App\Helpers\TrafficLight;
use App\Models\BacklogItem;
use App\Models\Catalog;
use App\Models\Developer;
use App\Models\Project;

class ProjectsController extends Controller
{
    public function indexAction(): void
    {
        $today = date('Y-m-d');
        $projectModel = new Project();

        $projects = array_map(function (array $p) use ($projectModel, $today) {
            $risk = $projectModel->riskCounters((int) $p['id'], $today);
            $p['traffic_light'] = TrafficLight::forProject(
                (float) $p['progress_percent'],
                $p['estimated_end_date'],
                $risk['overdue_activities'],
                $risk['critical_open_activities'],
                $p['status_code'] === 'completed',
                $today
            );
            $p['days_remaining'] = DateMath::daysRemaining($p['estimated_end_date'], $today);

            return $p;
        }, $projectModel->allWithDetails());

        // Roll each platform (proyecto padre) up from its children: progress is
        // the activity-weighted average, traffic light the worst child light.
        foreach ($projects as &$p) {
            if ((int) $p['is_platform'] !== 1) {
                continue;
            }
            $childRows = array_values(array_filter(
                $projects,
                fn (array $c) => (int) ($c['parent_id'] ?? 0) === (int) $p['id']
            ));
            $p['child_count'] = count($childRows);
            $p['progress_percent'] = Progress::projectProgress(array_map(
                fn (array $c) => ['progress_percent' => (float) $c['progress_percent'], 'activity_count' => (int) $c['activity_count']],
                $childRows
            ));
            $p['activity_count'] = array_sum(array_column($childRows, 'activity_count'));
            $p['traffic_light'] = TrafficLight::forPlatform(array_column($childRows, 'traffic_light'));
            $p['days_remaining'] = null;
        }
        unset($p);

        $this->render('projects/projects/index', [
            'pageTitle' => 'Proyectos',
            'activeModule' => 'projects-projects',
            'projects' => $projects,
            ...$this->formOptions(),
        ]);
    }

    public function createAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $model = new Project();
            $projectId = $model->insert($this->collectInput());
            $model->syncDevelopers($projectId, $this->collaboratorIds());
            $this->flash('success', 'Proyecto creado correctamente.');
            $this->redirect('projects/projects/index');
            return;
        }

        $prefillParent = (int) $this->input('parent_id', 0);

        $this->render('projects/projects/form', [
            'pageTitle' => 'Nuevo proyecto',
            'activeModule' => 'projects-projects',
            'project' => $prefillParent > 0 ? ['parent_id' => $prefillParent] : null,
            'collaborators' => [],
            ...$this->formOptions(),
        ]);
    }

    public function editAction(?string $id): void
    {
        $model = new Project();
        $project = $model->find((int) $id);

        if ($project === null) {
            $this->redirect('projects/projects/index');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $model->update((int) $id, $this->collectInput());
            $model->syncDevelopers((int) $id, $this->collaboratorIds());
            $this->flash('success', 'Proyecto actualizado correctamente.');
            $this->redirect('projects/projects/index');
            return;
        }

        $this->render('projects/projects/form', [
            'pageTitle' => 'Editar proyecto',
            'activeModule' => 'projects-projects',
            'project' => $project,
            'collaborators' => $model->additionalDevelopers((int) $id),
            ...$this->formOptions(),
        ]);
    }

    public function viewAction(?string $id): void
    {
        $projectId = (int) $id;
        $projectModel = new Project();
        $project = $projectModel->findWithDetails($projectId);

        if ($project === null) {
            $this->redirect('projects/projects/index');
            return;
        }

        $today = date('Y-m-d');

        if ((int) $project['is_platform'] === 1) {
            $this->renderPlatform($projectModel, $project, $today);
            return;
        }

        $risk = $projectModel->riskCounters($projectId, $today);
        $project['traffic_light'] = TrafficLight::forProject(
            (float) $project['progress_percent'],
            $project['estimated_end_date'],
            $risk['overdue_activities'],
            $risk['critical_open_activities'],
            $project['status_code'] === 'completed',
            $today
        );
        $project['days_remaining'] = DateMath::daysRemaining($project['estimated_end_date'], $today);
        $project['days_elapsed'] = $project['start_date'] ? DateMath::daysElapsed($project['start_date'], $today) : null;

        $backlogs = (new BacklogItem())->byProject($projectId);

        $this->render('projects/projects/view', [
            'pageTitle' => $project['name'],
            'activeModule' => 'projects-projects',
            'project' => $project,
            'backlogs' => $backlogs,
        ]);
    }

    /** Dedicated view for a platform (proyecto padre): aggregates + child list. */
    private function renderPlatform(Project $projectModel, array $project, string $today): void
    {
        $children = array_map(function (array $c) use ($projectModel, $today) {
            $risk = $projectModel->riskCounters((int) $c['id'], $today);
            $c['traffic_light'] = TrafficLight::forProject(
                (float) $c['progress_percent'],
                $c['estimated_end_date'],
                $risk['overdue_activities'],
                $risk['critical_open_activities'],
                $c['status_code'] === 'completed',
                $today
            );
            $c['days_remaining'] = DateMath::daysRemaining($c['estimated_end_date'], $today);
            $c['backlog_count'] = count((new BacklogItem())->byProject((int) $c['id']));

            return $c;
        }, $projectModel->children((int) $project['id']));

        $project['progress_percent'] = Progress::projectProgress(array_map(
            fn (array $c) => ['progress_percent' => (float) $c['progress_percent'], 'activity_count' => (int) $c['activity_count']],
            $children
        ));
        $project['activity_count'] = array_sum(array_column($children, 'activity_count'));
        $project['traffic_light'] = TrafficLight::forPlatform(array_column($children, 'traffic_light'));
        $project['days_elapsed'] = $project['start_date'] ? DateMath::daysElapsed($project['start_date'], $today) : null;

        $this->render('projects/projects/platform', [
            'pageTitle' => $project['name'],
            'activeModule' => 'projects-projects',
            'project' => $project,
            'children' => $children,
        ]);
    }

    public function deleteAction(?string $id): void
    {
        $model = new Project();
        $backlogCount = count((new BacklogItem())->byProject((int) $id));

        if ($backlogCount > 0) {
            $this->flash('error', 'No se puede eliminar: este proyecto tiene backlogs asociados.');
        } elseif ($model->hasChildren((int) $id)) {
            $this->flash('error', 'No se puede eliminar: esta plataforma tiene subproyectos asociados.');
        } else {
            $model->delete((int) $id);
            $this->flash('success', 'Proyecto eliminado.');
        }

        $this->redirect('projects/projects/index');
    }

    private function collectInput(): array
    {
        $isPlatform = (int) $this->input('is_platform') === 1 ? 1 : 0;

        // A platform has no parent and no end date; a child's parent must be a
        // real platform row (silently drop anything else).
        $parentId = (int) $this->input('parent_id');
        if ($isPlatform || $parentId <= 0) {
            $parentId = null;
        } else {
            $platformIds = array_map('intval', array_column((new Project())->platforms(), 'id'));
            if (!in_array($parentId, $platformIds, true)) {
                $parentId = null;
            }
        }

        return [
            'parent_id' => $parentId,
            'is_platform' => $isPlatform,
            'name' => trim((string) $this->input('name')),
            'developer_id' => (int) $this->input('developer_id'),
            'description' => $this->input('description') ?: null,
            'start_date' => $this->input('start_date') ?: null,
            'estimated_end_date' => $isPlatform ? null : ($this->input('estimated_end_date') ?: null),
            'actual_end_date' => $isPlatform ? null : ($this->input('actual_end_date') ?: null),
            'priority_id' => (int) $this->input('priority_id'),
            'sprint_duration_days' => max(1, (int) $this->input('sprint_duration_days', 8)),
            'status_id' => (int) $this->input('status_id'),
            'notes' => $this->input('notes') ?: null,
        ];
    }

    /** @return int[] developer_id values from the "colaboradores adicionales" multi-select, excluding the primary developer. */
    private function collaboratorIds(): array
    {
        $primary = (int) $this->input('developer_id');
        $ids = array_map('intval', (array) ($_POST['collaborator_ids'] ?? []));

        return array_values(array_filter($ids, fn (int $id) => $id > 0 && $id !== $primary));
    }

    private function formOptions(): array
    {
        return [
            'developers' => (new Developer())->all('name ASC'),
            'priorities' => (new Catalog('cat_priorities'))->all(),
            'statuses' => (new Catalog('cat_project_statuses'))->all(),
            'platforms' => (new Project())->platforms(),
        ];
    }
}
