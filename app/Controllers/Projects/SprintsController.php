<?php

namespace App\Controllers\Projects;

use App\Core\Controller;
use App\Helpers\SprintProgress;
use App\Models\BacklogItem;
use App\Models\Project;
use App\Models\Sprint;
use App\Services\Projects\SprintService;

class SprintsController extends Controller
{
    public function indexAction(): void
    {
        $projectFilter = (int) $this->input('project_id', 0);
        $projects = (new Project())->all('name ASC');
        $sprintModel = new Sprint();

        $sprints = [];
        foreach ($projects as $project) {
            if ($projectFilter > 0 && (int) $project['id'] !== $projectFilter) {
                continue;
            }

            foreach ($sprintModel->byProject((int) $project['id']) as $sprint) {
                $sprint['project_name'] = $project['name'];
                $sprints[] = $sprint;
            }
        }

        $this->render('projects/sprints/index', [
            'pageTitle' => 'Sprints',
            'activeModule' => 'projects-sprints',
            'sprints' => $sprints,
            'projects' => $projects,
            'projectFilter' => $projectFilter,
        ]);
    }

    public function viewAction(?string $id): void
    {
        $sprintId = (int) $id;
        $sprint = (new Sprint())->findWithProject($sprintId);

        if ($sprint === null) {
            $this->redirect('projects/sprints/index');
            return;
        }

        $items = (new BacklogItem())->bySprint($sprintId);

        $this->render('projects/sprints/view', [
            'pageTitle' => 'Sprint #' . $sprint['sequence_number'] . ' — ' . $sprint['project_name'],
            'activeModule' => 'projects-sprints',
            'sprint' => $sprint,
            'items' => $items,
            'livePercent' => SprintProgress::completionPercent($items),
        ]);
    }

    /** Opens the next sprint for a project (POST project_id[, process_owner]). */
    public function createAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $projectId = (int) $this->input('project_id');
            $processOwner = $this->input('process_owner') ?: null;

            if ($projectId > 0) {
                (new SprintService())->openSprint($projectId, $processOwner);
                $this->flash('success', 'Sprint abierto correctamente.');
            }
        }

        $this->redirect('projects/sprints/index');
    }

    /** Closes a sprint: records its % de cumplimiento and rolls unfinished backlog into the next sprint. */
    public function closeAction(?string $id): void
    {
        (new SprintService())->closeSprint((int) $id);
        $this->flash('success', 'Sprint cerrado. El % de cumplimiento quedó registrado y las tareas pendientes pasaron al siguiente sprint.');
        $this->redirect('projects/sprints/index');
    }
}
