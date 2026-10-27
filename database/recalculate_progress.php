<?php
/**
 * One-time recalculation for the 2026-10-26 progress rules (hours-weighted
 * progress, computed statuses, delay, sprint cumplimiento).
 *
 *   php database/recalculate_progress.php --dry-run out.json
 *       Read-only. Writes, for every backlog / project / platform / sprint,
 *       the current % and status next to the new ones (the simulation).
 *       Works before the migration too (every activity counts as 1 h).
 *
 *   php database/recalculate_progress.php --apply
 *       Needs migration_2026_10_26_hours_weighted_progress.sql applied.
 *       Stores the computed statuses (special manual ones untouched) and
 *       freezes the cumplimiento of closed sprints that don't have it yet.
 *
 * The target database comes from .env, or from DB_* environment variables,
 * which take precedence.
 */

require_once __DIR__ . '/../config/config.php';

spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $path = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($path)) {
            require $path;
        }
    }
});

use App\Core\Database;
use App\Helpers\Delay;
use App\Helpers\Progress;
use App\Helpers\SprintProgress;
use App\Helpers\WorkStatus;
use App\Models\Activity;
use App\Models\BacklogItem;
use App\Models\Project;
use App\Services\Projects\StatusSyncService;

$mode = $argv[1] ?? '--dry-run';
$today = date('Y-m-d');
$db = Database::connection();
$activityModel = new Activity();
$hasHours = $activityModel->hasEstimatedHours();

if ($mode === '--apply') {
    if (!$hasHours) {
        fwrite(STDERR, "Run migration_2026_10_26_hours_weighted_progress.sql first.\n");
        exit(1);
    }
    $db->beginTransaction();
    $updated = (new StatusSyncService())->apply();
    $frozen = $db->exec(
        // Closed sprints freeze their cumplimiento; without a recorded commitment
        // (older sprints) it is measured against their current hours.
        "UPDATE sprints s SET compliance_percent = ROUND(100 * (
            SELECT COALESCE(SUM(COALESCE(a.estimated_hours, 1)), 0) FROM sprint_backlog sb
            JOIN activities a ON a.backlog_item_id = sb.backlog_item_id
            JOIN cat_activity_statuses st ON st.id = a.status_id AND st.code = 'completed'
            WHERE sb.sprint_id = s.id) / NULLIF(COALESCE(s.committed_hours, (
                SELECT SUM(COALESCE(a.estimated_hours, 1)) FROM sprint_backlog sb
                JOIN activities a ON a.backlog_item_id = sb.backlog_item_id
                JOIN cat_activity_statuses st ON st.id = a.status_id AND st.code <> 'cancelled'
                WHERE sb.sprint_id = s.id)), 0), 1)
         WHERE s.status = 'closed' AND s.compliance_percent IS NULL"
    );
    $db->commit();
    echo "Statuses updated: {$updated}; closed sprints with cumplimiento: {$frozen}\n";
    exit(0);
}

$out = $argv[2] ?? 'php://stdout';

// --- activities -----------------------------------------------------------
$byBacklog = [];
$byProject = [];
foreach ($activityModel->rollupRows($hasHours) as $a) {
    $byBacklog[(int) $a['backlog_item_id']][] = $a;
    $byProject[(int) $a['project_id']][] = $a;
}

/** The rule being replaced: plain average per backlog (cancelled included, rounded like the old view). */
$oldBacklog = fn (array $acts): float => $acts === [] ? 0.0
    : round(array_sum(array_map(fn ($a) => (float) $a['progress_percent'], $acts)) / count($acts));

$newRow = function (array $acts) use ($today): array {
    $live = array_values(array_filter($acts, fn ($a) => $a['status_code'] !== 'cancelled'));
    $delay = Delay::fromActivities($acts, $today);

    return [
        'new_pct' => round(Progress::byHours($acts), 1),
        'activities' => count($live),
        'cancelled' => count($acts) - count($live),
        'hours' => array_sum(array_map(fn ($a) => Progress::hoursOf($a), $live)),
        'overdue' => $delay['overdue_count'],
        'days_late' => $delay['days_late'],
        'derived' => WorkStatus::derive($acts),
    ];
};

// --- backlogs ---------------------------------------------------------------
$projects = [];
foreach ((new Project())->statusRows() as $p) {
    $projects[(int) $p['id']] = $p;
}

$backlogRows = [];
$oldByBacklog = [];
foreach ((new BacklogItem())->statusRows() as $b) {
    $acts = $byBacklog[(int) $b['id']] ?? [];
    $oldByBacklog[(int) $b['id']] = ['pct' => $oldBacklog($acts), 'count' => count($acts), 'project_id' => (int) $b['project_id']];
    $r = $newRow($acts);
    $backlogRows[] = [
        'id' => (int) $b['id'],
        'project' => $projects[(int) $b['project_id']]['name'] ?? '',
        'name' => $b['description'],
        'old_pct' => $oldByBacklog[(int) $b['id']]['pct'],
        'old_status' => $b['status_code'],
        'new_status' => WorkStatus::forBacklog($b['status_code'], $r['derived']),
    ] + $r;
}

// --- projects and platforms ---------------------------------------------------
$oldProject = function (int $projectId) use ($oldByBacklog): array {
    $sum = 0.0;
    $count = 0;
    foreach ($oldByBacklog as $b) {
        if ($b['project_id'] === $projectId) {
            $sum += $b['pct'] * $b['count'];
            $count += $b['count'];
        }
    }

    return ['pct' => $count > 0 ? $sum / $count : 0.0, 'count' => $count];
};

$projectRows = [];
foreach ($projects as $id => $p) {
    $acts = $byProject[$id] ?? [];
    $old = $oldProject($id);
    if ((int) $p['is_platform'] === 1) {
        // Old rule: children's % weighted by their activity count. New: all their activities by hours.
        $sum = 0.0;
        $count = 0;
        foreach ($projects as $cid => $c) {
            if ((int) ($c['parent_id'] ?? 0) === $id) {
                array_push($acts, ...($byProject[$cid] ?? []));
                $co = $oldProject($cid);
                $sum += $co['pct'] * $co['count'];
                $count += $co['count'];
            }
        }
        $old = ['pct' => $count > 0 ? $sum / $count : 0.0, 'count' => $count];
    }
    $r = $newRow($acts);
    $projectRows[] = [
        'id' => $id,
        'level' => (int) $p['is_platform'] === 1 ? 'Plataforma' : ($p['parent_id'] !== null ? 'Subproyecto' : 'Proyecto'),
        'parent' => $p['parent_id'] !== null ? ($projects[(int) $p['parent_id']]['name'] ?? '') : '',
        'name' => $p['name'],
        'old_pct' => round($old['pct'], 1),
        'old_status' => $p['status_code'],
        'new_status' => WorkStatus::forProject($p['status_code'], $r['derived']),
    ] + $r;
}

// --- sprints -------------------------------------------------------------------
$sprintRows = [];
foreach ($db->query('SELECT * FROM sprints ORDER BY start_date DESC, id DESC')->fetchAll() as $s) {
    $ids = array_map('intval', array_column(
        $db->query('SELECT backlog_item_id FROM sprint_backlog WHERE sprint_id = ' . (int) $s['id'])->fetchAll(), 'backlog_item_id'));
    $acts = array_merge([], ...array_map(fn ($id) => $byBacklog[$id] ?? [], $ids));
    $sum = 0.0;
    $count = 0;
    foreach ($ids as $id) {
        $sum += ($oldByBacklog[$id]['pct'] ?? 0) * ($oldByBacklog[$id]['count'] ?? 0);
        $count += $oldByBacklog[$id]['count'] ?? 0;
    }
    $r = $newRow($acts);
    $doneHours = array_sum(array_map(fn ($a) => Progress::hoursOf($a), array_filter($acts, fn ($a) => $a['status_code'] === 'completed')));
    $committed = isset($s['committed_hours']) && $s['committed_hours'] !== null ? (float) $s['committed_hours'] : $r['hours'];
    $sprintRows[] = [
        'id' => (int) $s['id'],
        'name' => $s['name'] ?: 'Sprint #' . $s['id'],
        'status' => $s['status'],
        'period' => $s['start_date'] . ' — ' . $s['end_date'],
        'backlogs' => count($ids),
        'old_pct' => $s['status'] === 'closed' && $s['completion_percent'] !== null ? (float) $s['completion_percent'] : round($count > 0 ? $sum / $count : 0, 1),
        'committed_hours' => $committed,
        'done_hours' => $doneHours,
        'compliance' => SprintProgress::compliancePercent($doneHours, $committed),
    ] + $r;
}

// --- activities the new rule 1 flags ---------------------------------------------
$activityFlags = [];
foreach ($db->query(
    "SELECT a.id, a.name, a.progress_percent, s.code AS status_code, b.description AS backlog, p.name AS project
     FROM activities a JOIN cat_activity_statuses s ON s.id = a.status_id
     JOIN backlog_items b ON b.id = a.backlog_item_id JOIN projects p ON p.id = b.project_id
     WHERE (s.code = 'completed' AND a.progress_percent < 100) OR (s.code <> 'completed' AND a.progress_percent >= 100)"
)->fetchAll() as $a) {
    $activityFlags[] = $a;
}

file_put_contents($out, json_encode([
    'generated_at' => date('Y-m-d H:i'),
    'database' => DB_NAME . '@' . DB_HOST,
    'has_estimated_hours' => $hasHours,
    'backlogs' => $backlogRows,
    'projects' => $projectRows,
    'sprints' => $sprintRows,
    'activity_flags' => $activityFlags,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
