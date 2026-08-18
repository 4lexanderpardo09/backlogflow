<?php

namespace App\Controllers\Projects;

use App\Core\Controller;
use App\Helpers\DateMath;
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
        $projects = $projectModel->allWithDetails();

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
        }, $projects);

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
            (new Project())->insert($this->collectInput());
            $this->flash('success', 'Proyecto creado correctamente.');
            $this->redirect('projects/projects/index');
            return;
        }

        $this->render('projects/projects/form', [
            'pageTitle' => 'Nuevo proyecto',
            'activeModule' => 'projects-projects',
            'project' => null,
            ...$this->formOptions(),
        ]);
    }

    public function editAction(?string $id): void
    {
        $project = (new Project())->find((int) $id);

        if ($project === null) {
            $this->redirect('projects/projects/index');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new Project())->update((int) $id, $this->collectInput());
            $this->flash('success', 'Proyecto actualizado correctamente.');
            $this->redirect('projects/projects/index');
            return;
        }

        $this->render('projects/projects/form', [
            'pageTitle' => 'Editar proyecto',
            'activeModule' => 'projects-projects',
            'project' => $project,
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

    public function deleteAction(?string $id): void
    {
        $model = new Project();
        $backlogCount = count((new BacklogItem())->byProject((int) $id));

        if ($backlogCount > 0) {
            $this->flash('error', 'No se puede eliminar: este proyecto tiene backlogs asociados.');
        } else {
            $model->delete((int) $id);
            $this->flash('success', 'Proyecto eliminado.');
        }

        $this->redirect('projects/projects/index');
    }

    private function collectInput(): array
    {
        return [
            'name' => trim((string) $this->input('name')),
            'developer_id' => (int) $this->input('developer_id'),
            'description' => $this->input('description') ?: null,
            'start_date' => $this->input('start_date') ?: null,
            'estimated_end_date' => $this->input('estimated_end_date') ?: null,
            'actual_end_date' => $this->input('actual_end_date') ?: null,
            'priority_id' => (int) $this->input('priority_id'),
            'status_id' => (int) $this->input('status_id'),
            'notes' => $this->input('notes') ?: null,
        ];
    }

    private function formOptions(): array
    {
        return [
            'developers' => (new Developer())->all('name ASC'),
            'priorities' => (new Catalog('cat_priorities'))->all(),
            'statuses' => (new Catalog('cat_project_statuses'))->all(),
        ];
    }
}
