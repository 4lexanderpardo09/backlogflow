<?php

namespace App\Services\Projects;

use App\Helpers\WorkStatus;
use App\Models\Activity;
use App\Models\BacklogItem;
use App\Models\Catalog;
use App\Models\Project;

/**
 * Keeps the stored status of backlogs, sub-projects, projects and platforms
 * in line with their activities (App\Helpers\WorkStatus): it is recomputed
 * every time an activity, a backlog or a project is saved, so the lists,
 * filters and badges that read the status column never show a "Pendiente"
 * backlog at 100 % again. Special manual statuses (En análisis, Bloqueado,
 * En pausa, Cancelado...) are left untouched.
 *
 * plan() only computes what would change, without writing — it is what the
 * simulation and the one-time recalculation script use.
 */
class StatusSyncService
{
    public function __construct(
        private readonly Activity $activityModel = new Activity(),
        private readonly BacklogItem $backlogModel = new BacklogItem(),
        private readonly Project $projectModel = new Project(),
    ) {
    }

    /**
     * Every stored status that differs from the computed one.
     *
     * @return array{backlogs: array<int, array>, projects: array<int, array>}
     */
    public function plan(): array
    {
        $byBacklog = [];
        $byProject = [];
        foreach ($this->activityModel->rollupRows($this->activityModel->hasEstimatedHours()) as $a) {
            $byBacklog[(int) $a['backlog_item_id']][] = $a;
            $byProject[(int) $a['project_id']][] = $a;
        }

        $backlogs = [];
        foreach ($this->backlogModel->statusRows() as $b) {
            $derived = WorkStatus::derive($byBacklog[(int) $b['id']] ?? []);
            $new = WorkStatus::forBacklog($b['status_code'], $derived);
            if ($new !== $b['status_code']) {
                $backlogs[] = ['id' => (int) $b['id'], 'project_id' => (int) $b['project_id'], 'name' => $b['description'],
                    'current' => $b['status_code'], 'new' => $new];
            }
        }

        $projectRows = $this->projectModel->statusRows();
        $projects = [];
        foreach ($projectRows as $p) {
            // A platform has no backlog of its own: it is made of its sub-projects' activities.
            $activities = $byProject[(int) $p['id']] ?? [];
            if ((int) $p['is_platform'] === 1) {
                foreach ($projectRows as $child) {
                    if ((int) ($child['parent_id'] ?? 0) === (int) $p['id']) {
                        array_push($activities, ...($byProject[(int) $child['id']] ?? []));
                    }
                }
            }
            $new = WorkStatus::forProject($p['status_code'], WorkStatus::derive($activities));
            if ($new !== $p['status_code']) {
                $projects[] = ['id' => (int) $p['id'], 'parent_id' => $p['parent_id'] !== null ? (int) $p['parent_id'] : null,
                    'name' => $p['name'], 'current' => $p['status_code'], 'new' => $new];
            }
        }

        return ['backlogs' => $backlogs, 'projects' => $projects];
    }

    /**
     * Writes the planned changes, optionally only for some backlogs / projects.
     *
     * @param int[]|null $backlogIds null = all
     * @param int[]|null $projectIds null = all
     * @return int number of rows updated
     */
    public function apply(?array $backlogIds = null, ?array $projectIds = null): int
    {
        $plan = $this->plan();
        $backlogStatus = array_column((new Catalog('cat_backlog_statuses'))->all(), 'id', 'code');
        $projectStatus = array_column((new Catalog('cat_project_statuses'))->all(), 'id', 'code');
        $updated = 0;

        foreach ($plan['backlogs'] as $c) {
            if ($backlogIds === null || in_array($c['id'], $backlogIds, true)) {
                $this->backlogModel->update($c['id'], ['status_id' => $backlogStatus[$c['new']]]);
                $updated++;
            }
        }
        foreach ($plan['projects'] as $c) {
            if ($projectIds === null || in_array($c['id'], $projectIds, true)) {
                $this->projectModel->update($c['id'], ['status_id' => $projectStatus[$c['new']]]);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * After activities / backlogs changed: recomputes those backlogs, their
     * projects and the platforms above them.
     *
     * @param int[] $backlogIds
     * @param int[] $projectIds projects touched directly (e.g. a backlog moved out of one)
     */
    public function sync(array $backlogIds, array $projectIds = []): void
    {
        $backlogIds = array_values(array_unique(array_filter(array_map('intval', $backlogIds))));
        $projectIds = array_map('intval', $projectIds);

        foreach ($backlogIds as $id) {
            $row = $this->backlogModel->find($id);
            if ($row !== null) {
                $projectIds[] = (int) $row['project_id'];
            }
        }

        $parents = [];
        foreach (array_unique(array_filter($projectIds)) as $id) {
            $row = $this->projectModel->find($id);
            if ($row !== null && $row['parent_id'] !== null) {
                $parents[] = (int) $row['parent_id'];
            }
        }

        $this->apply($backlogIds, array_values(array_unique(array_filter([...$projectIds, ...$parents]))));
    }
}
