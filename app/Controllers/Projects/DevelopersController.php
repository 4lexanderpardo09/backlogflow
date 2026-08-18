<?php

namespace App\Controllers\Projects;

use App\Core\Controller;
use App\Helpers\ActivityStatus;
use App\Models\Activity;
use App\Models\BacklogItem;
use App\Models\Developer;
use App\Models\Project;

class DevelopersController extends Controller
{
    public function indexAction(): void
    {
        $developers = (new Developer())->allWithProjectName();

        $this->render('projects/developers/index', [
            'pageTitle' => 'Desarrolladores',
            'activeModule' => 'projects-developers',
            'developers' => $developers,
        ]);
    }

    public function createAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new Developer())->insert($this->collectInput());
            $this->flash('success', 'Desarrollador creado correctamente.');
            $this->redirect('projects/developers/index');
            return;
        }

        $this->render('projects/developers/form', [
            'pageTitle' => 'Nuevo desarrollador',
            'activeModule' => 'projects-developers',
            'developer' => null,
        ]);
    }

    public function editAction(?string $id): void
    {
        $developer = (new Developer())->find((int) $id);

        if ($developer === null) {
            $this->redirect('projects/developers/index');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new Developer())->update((int) $id, $this->collectInput());
            $this->flash('success', 'Desarrollador actualizado correctamente.');
            $this->redirect('projects/developers/index');
            return;
        }

        $this->render('projects/developers/form', [
            'pageTitle' => 'Editar desarrollador',
            'activeModule' => 'projects-developers',
            'developer' => $developer,
        ]);
    }

    public function deleteAction(?string $id): void
    {
        $model = new Developer();

        if ($model->isReferenced((int) $id)) {
            $this->flash('error', 'No se puede eliminar: este desarrollador tiene proyectos, backlogs o actividades asignadas.');
        } else {
            $model->delete((int) $id);
            $this->flash('success', 'Desarrollador eliminado.');
        }

        $this->redirect('projects/developers/index');
    }

    /** "Vista por desarrollador": project, backlogs, activities and alerts scoped to one developer. */
    public function viewAction(?string $id): void
    {
        $developerId = (int) $id;
        $developer = (new Developer())->find($developerId);

        if ($developer === null) {
            $this->redirect('projects/developers/index');
            return;
        }

        $today = date('Y-m-d');
        $projects = (new Project())->byDeveloper($developerId);
        $backlogs = (new BacklogItem())->allWithDetails();
        $backlogs = array_values(array_filter($backlogs, fn ($b) => (int) $b['developer_id'] === $developerId));

        $activities = (new Activity())->byDeveloper($developerId);
        $activities = array_map(function (array $a) use ($today) {
            $hasUnresolvedDependency = $a['depends_on_activity_id'] !== null
                && (int) ($a['depends_on_progress'] ?? 0) < 100;
            $a['system_status'] = ActivityStatus::compute((int) $a['progress_percent'], $a['due_date'], $hasUnresolvedDependency, $today);
            return $a;
        }, $activities);

        $pending = array_filter($activities, fn ($a) => $a['system_status'] === ActivityStatus::PENDING);
        $overdue = array_filter($activities, fn ($a) => $a['system_status'] === ActivityStatus::OVERDUE);
        $highPriority = array_filter($activities, fn ($a) => in_array($a['priority_code'], ['critical', 'high'], true) && $a['system_status'] !== ActivityStatus::COMPLETED);

        $overallProgress = $projects === [] ? 0.0 : array_sum(array_column($projects, 'progress_percent')) / count($projects);

        $this->render('projects/developers/view', [
            'pageTitle' => 'Desarrollador: ' . $developer['name'],
            'activeModule' => 'projects-developers',
            'developer' => $developer,
            'projects' => $projects,
            'backlogs' => $backlogs,
            'activities' => $activities,
            'pendingActivities' => array_values($pending),
            'overdueActivities' => array_values($overdue),
            'highPriorityActivities' => array_values($highPriority),
            'overallProgress' => round($overallProgress, 1),
        ]);
    }

    private function collectInput(): array
    {
        return [
            'name' => trim((string) $this->input('name')),
            'position' => $this->input('position') ?: null,
            'status' => $this->input('status', 'active'),
            'start_date' => $this->input('start_date') ?: null,
            'notes' => $this->input('notes') ?: null,
        ];
    }
}
