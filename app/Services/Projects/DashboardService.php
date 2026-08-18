<?php

namespace App\Services\Projects;

use App\Helpers\ActivityStatus;
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
        $activities = array_map(fn (array $a) => $this->withComputedStatus($a, $today), $activities);

        if (!empty($filters['developer_id'])) {
            $developerId = (int) $filters['developer_id'];
            $projects = array_values(array_filter($projects, fn ($p) => (int) $p['developer_id'] === $developerId));
        }
        if (!empty($filters['priority'])) {
            $projects = array_values(array_filter($projects, fn ($p) => $p['priority_code'] === $filters['priority']));
        }
        if (!empty($filters['status'])) {
            $projects = array_values(array_filter($projects, fn ($p) => $p['status_code'] === $filters['status']));
        }

        if ($filters !== [] && array_filter($filters) !== []) {
            $projectIds = array_map('intval', array_column($projects, 'id'));
            $backlogs = array_values(array_filter($backlogs, fn ($b) => in_array((int) $b['project_id'], $projectIds, true)));
            $activities = array_values(array_filter($activities, fn ($a) => in_array((int) $a['project_id'], $projectIds, true)));
        }

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

        $overallProgress = $projects === [] ? 0.0 : array_sum(array_column($projects, 'progress_percent')) / count($projects);

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
