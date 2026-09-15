<?php

namespace App\Services\Projects;

use App\Helpers\ActivityStatus;
use App\Helpers\Progress;
use App\Helpers\TrafficLight;
use App\Models\Activity;
use App\Models\BacklogItem;
use App\Models\Developer;
use App\Models\Project;

/**
 * Builds every aggregate the Projects dashboard needs. Reads raw rows from
 * the Models (data access only) and applies the Helpers (pure business
 * rules) to derive status/traffic-light/alerts — keeping that logic out of
 * both the Models and the Controller.
 */
class DashboardService
{
    public function __construct(
        private readonly Project $projectModel = new Project(),
        private readonly BacklogItem $backlogModel = new BacklogItem(),
        private readonly Activity $activityModel = new Activity(),
        private readonly Developer $developerModel = new Developer(),
    ) {
    }

    /**
     * @param array{developer_id?: string, priority?: string, status?: string} $filters
     *        Empty/absent values mean "no filter" for that dimension. Filtering by
     *        developer/priority/status narrows the project set first, then backlogs
     *        and activities are narrowed to only those belonging to the filtered
     *        projects — so the whole dashboard, not just the project table, reacts.
     */
    public function summary(array $filters = [], ?string $today = null): array
    {
        $today ??= date('Y-m-d');

        $projects = $this->projectModel->allWithDetails();
        $backlogs = $this->backlogModel->allWithDetails();
        $activities = $this->activityModel->allWithDetails();

        $projects = array_map(fn (array $p) => $this->withTrafficLight($p, $today), $projects);
        $projects = $this->rollUpPlatforms($projects);
        $activities = array_map(fn (array $a) => $this->withComputedStatus($a, $today), $activities);

        $childrenOf = fn (int $parentId): array => array_values(array_filter(
            $projects,
            fn (array $c) => (int) ($c['parent_id'] ?? 0) === $parentId
        ));

        // A platform is shown as its rolled-up row, so its sub-projects only
        // appear (instead of it) once that platform is picked in the filter.
        $projectFilter = (int) ($filters['project_id'] ?? 0);
        $selected = $projectFilter > 0
            ? current(array_filter($projects, fn (array $p) => (int) $p['id'] === $projectFilter))
            : false;

        if ($selected !== false && (int) $selected['is_platform'] === 1) {
            $shown = $childrenOf($projectFilter);
        } elseif ($selected !== false) {
            $shown = [$selected];
        } else {
            $shown = array_values(array_filter($projects, fn (array $p) => (int) ($p['parent_id'] ?? 0) === 0));
        }

        if (!empty($filters['developer_id'])) {
            $developerId = (string) (int) $filters['developer_id'];
            $worksOn = fn (array $p): bool => (string) $p['developer_id'] === $developerId
                || in_array($developerId, explode(',', (string) ($p['collaborator_ids'] ?? '')), true);
            $shown = array_values(array_filter(
                $shown,
                fn (array $p) => $worksOn($p)
                    || ((int) $p['is_platform'] === 1 && array_filter($childrenOf((int) $p['id']), $worksOn) !== [])
            ));
        }
        if (!empty($filters['priority'])) {
            $shown = array_values(array_filter($shown, fn ($p) => $p['priority_code'] === $filters['priority']));
        }
        if (!empty($filters['status'])) {
            $shown = array_values(array_filter($shown, fn ($p) => $p['status_code'] === $filters['status']));
        }

        // Backlogs and activities follow the shown projects, including the
        // sub-projects behind any platform row.
        $scopeIds = [];
        foreach ($shown as $p) {
            $scopeIds[] = (int) $p['id'];
            if ((int) $p['is_platform'] === 1) {
                array_push($scopeIds, ...array_map(fn (array $c) => (int) $c['id'], $childrenOf((int) $p['id'])));
            }
        }
        $backlogs = array_values(array_filter($backlogs, fn ($b) => in_array((int) $b['project_id'], $scopeIds, true)));
        $activities = array_values(array_filter($activities, fn ($a) => in_array((int) $a['project_id'], $scopeIds, true)));
        $projects = $shown;

        $overdueActivities = array_values(array_filter(
            $activities,
            fn (array $a) => $a['system_status'] === ActivityStatus::OVERDUE
        ));
        $dueSoonActivities = array_values(array_filter(
            $activities,
            fn (array $a) => ActivityStatus::isDueSoon((int) $a['progress_percent'], $a['due_date'], 5, $today)
        ));

        $activeStatuses = ['not_started', 'in_progress', 'on_hold', 'delayed'];
        $activeProjects = array_values(array_filter($projects, fn ($p) => in_array($p['status_code'], $activeStatuses, true)));
        $finishedProjects = array_values(array_filter($projects, fn ($p) => $p['status_code'] === 'completed'));
        $delayedProjects = array_values(array_filter($projects, fn ($p) => $p['status_code'] === 'delayed' || $p['traffic_light'] === TrafficLight::RED));
        $highPriorityProjects = array_values(array_filter($projects, fn ($p) => in_array($p['priority_code'], ['critical', 'high'], true)));

        // Weighted by activity count — the same rule a platform uses for its
        // children — so a one-task project doesn't weigh as much as a large one.
        $overallProgress = Progress::projectProgress(array_map(
            fn (array $p) => ['progress_percent' => (float) $p['progress_percent'], 'activity_count' => (int) $p['activity_count']],
            $projects
        ));

        usort($projects, fn ($a, $b) => $b['progress_percent'] <=> $a['progress_percent']);
        $topProject = $projects[0] ?? null;
        $bottomProject = $projects === [] ? null : $projects[count($projects) - 1];

        return [
            'today' => $today,
            'kpi' => [
                'total_projects' => count($projects),
                'active_projects' => count($activeProjects),
                'finished_projects' => count($finishedProjects),
                'delayed_projects' => count($delayedProjects),
                'total_backlogs' => count($backlogs),
                'total_activities' => count($activities),
                'pending_activities' => count(array_filter($activities, fn ($a) => $a['system_status'] === ActivityStatus::PENDING)),
                'in_progress_activities' => count(array_filter($activities, fn ($a) => $a['system_status'] === ActivityStatus::IN_PROGRESS)),
                'completed_activities' => count(array_filter($activities, fn ($a) => $a['system_status'] === ActivityStatus::COMPLETED)),
                'overall_progress' => round($overallProgress, 1),
            ],
            'projects' => $projects,
            'top_project' => $topProject,
            'bottom_project' => $bottomProject,
            'high_priority_projects' => $highPriorityProjects,
            'overdue_activities' => $overdueActivities,
            'due_soon_activities' => $dueSoonActivities,
            'status_distribution' => $this->countBy($projects, 'status_code'),
            'priority_distribution' => $this->countBy($projects, 'priority_code'),
            'progress_by_developer' => $this->progressByDeveloper($projects),
        ];
    }

    private function withTrafficLight(array $project, string $today): array
    {
        $risk = $this->projectModel->riskCounters((int) $project['id'], $today);

        $project['overdue_activities'] = $risk['overdue_activities'];
        $project['critical_open_activities'] = $risk['critical_open_activities'];
        $project['traffic_light'] = TrafficLight::forProject(
            (float) $project['progress_percent'],
            $project['estimated_end_date'],
            $risk['overdue_activities'],
            $risk['critical_open_activities'],
            $project['status_code'] === 'completed',
            $today
        );

        return $project;
    }

    /**
     * Platforms have no activities of their own, so the view gives them 0%.
     * Roll them up from their children with the same rule as the Projects
     * list: activity-weighted progress and the worst child traffic light.
     */
    private function rollUpPlatforms(array $projects): array
    {
        foreach ($projects as &$p) {
            if ((int) $p['is_platform'] !== 1) {
                continue;
            }
            $childRows = array_values(array_filter(
                $projects,
                fn (array $c) => (int) ($c['parent_id'] ?? 0) === (int) $p['id']
            ));
            $p['progress_percent'] = Progress::projectProgress(array_map(
                fn (array $c) => ['progress_percent' => (float) $c['progress_percent'], 'activity_count' => (int) $c['activity_count']],
                $childRows
            ));
            $p['activity_count'] = array_sum(array_column($childRows, 'activity_count'));
            $p['traffic_light'] = TrafficLight::forPlatform(array_column($childRows, 'traffic_light'));
        }
        unset($p);

        return $projects;
    }

    private function withComputedStatus(array $activity, string $today): array
    {
        $hasUnresolvedDependency = $activity['depends_on_activity_id'] !== null
            && (int) ($activity['depends_on_progress'] ?? 0) < 100;

        $activity['system_status'] = ActivityStatus::compute(
            (int) $activity['progress_percent'],
            $activity['due_date'],
            $hasUnresolvedDependency,
            $today
        );

        return $activity;
    }

    private function countBy(array $rows, string $column): array
    {
        $counts = [];
        foreach ($rows as $row) {
            $key = $row[$column] ?? 'unknown';
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return $counts;
    }

    private function progressByDeveloper(array $projects): array
    {
        $byDeveloper = [];
        foreach ($projects as $project) {
            $byDeveloper[$project['developer_name']][] = (float) $project['progress_percent'];
        }

        $result = [];
        foreach ($byDeveloper as $developer => $values) {
            $result[$developer] = round(array_sum($values) / count($values), 1);
        }

        return $result;
    }
}
