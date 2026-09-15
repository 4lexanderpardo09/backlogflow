<?php

namespace App\Controllers\Projects;

use App\Core\Controller;
use App\Models\Project;
use App\Services\Projects\CronogramaService;

class CronogramaController extends Controller
{
    public function indexAction(): void
    {
        $projectModel = new Project();
        $platforms = $projectModel->platforms();

        $parentId = (int) $this->input('parent_id', 0);
        $childId = (int) $this->input('child_id', 0);

        $children = $parentId > 0 ? $projectModel->children($parentId) : [];

        // Which projects feed the chart: one child if picked, else every child
        // of the chosen platform.
        if ($childId > 0) {
            $projectIds = [$childId];
        } elseif ($parentId > 0) {
            $projectIds = array_map(fn (array $c) => (int) $c['id'], $children);
        } else {
            $projectIds = [];
        }

        // Optional window chosen by the user; without both dates the service
        // picks it automatically (sprint span, else the current month).
        $from = $this->dateInput('desde');
        $to = $this->dateInput('hasta');

        $gantt = $projectIds === []
            ? ['window_start' => date('Y-m-01'), 'window_end' => date('Y-m-t'), 'rows' => []]
            : (new CronogramaService())->gantt($projectIds, $from, $to);

        $this->render('projects/cronograma/index', [
            'pageTitle' => 'Cronograma',
            'activeModule' => 'projects-cronograma',
            'platforms' => $platforms,
            'children' => $children,
            'parentId' => $parentId,
            'childId' => $childId,
            'from' => $from,
            'to' => $to,
            'gantt' => $gantt,
        ]);
    }
}
