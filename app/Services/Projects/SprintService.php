<?php

namespace App\Services\Projects;

use App\Core\Database;
use App\Helpers\Progress;
use App\Helpers\SprintClose;
use App\Helpers\SprintScope;
use App\Models\Activity;
use App\Models\Catalog;
use App\Models\Sprint;
use App\Models\SprintActivity;
use App\Models\SprintClosure;
use Throwable;

/**
 * Life cycle of a manually managed sprint: create, edit (name, goal, dates,
 * projects and activities), close with the wizard (decide what happens to
 * each pending activity, save the snapshot, move activities to the next
 * sprint) and reopen. A closed sprint is read-only.
 *
 * Activities added before or on the start date are committed (their hours
 * are frozen as the commitment); added after it, they are alcance agregado.
 */
class SprintService
{
    public function __construct(
        private readonly Sprint $sprintModel = new Sprint(),
        private readonly SprintActivity $membership = new SprintActivity(),
        private readonly SprintClosure $closureModel = new SprintClosure(),
    ) {
    }

    /**
     * @param array{name:?string,goal:?string,start_date:string,end_date:string,process_owner:?string,notes:?string} $data
     * @param int[] $projectIds
     * @param int[] $activityIds
     * @return array{id:int, skipped:string[]} skipped = activities left out because they are in another open sprint
     */
    public function createSprint(array $data, array $projectIds, array $activityIds, ?int $by = null, ?string $today = null): array
    {
        $sprintId = $this->sprintModel->insert($this->rowFrom($data) + ['status' => 'open']);
        $this->sprintModel->syncProjects($sprintId, $projectIds);
        // What a sprint is created with is its commitment, even when its start date is already past.
        $skipped = $this->addActivities($sprintId, $activityIds, $by, $today, SprintScope::COMMITTED);
        $this->membership->syncBacklogs($sprintId);
        $this->sprintModel->update($sprintId, ['committed_hours' => $this->membership->commitHours($sprintId)]);

        return ['id' => $sprintId, 'skipped' => $skipped];
    }

    /**
     * @param array{name:?string,goal:?string,start_date:string,end_date:string,process_owner:?string,notes:?string} $data
     * @param int[] $projectIds
     * @param int[] $activityIds
     * @return array{skipped:string[]}|null null when the sprint doesn't exist or is closed (read-only)
     */
    public function updateSprint(int $sprintId, array $data, array $projectIds, array $activityIds, ?int $by = null, ?string $today = null): ?array
    {
        $today ??= date('Y-m-d');
        $sprint = $this->sprintModel->find($sprintId);
        if ($sprint === null || !SprintClose::isEditable($sprint)) {
            return null;
        }

        $this->sprintModel->update($sprintId, $this->rowFrom($data));
        $this->sprintModel->syncProjects($sprintId, $projectIds);

        $current = array_keys($this->membership->activeIds($sprintId));
        $wanted = array_values(array_unique(array_map('intval', $activityIds)));
        $started = SprintScope::hasStarted($data['start_date'], $today);
        foreach (array_diff($current, $wanted) as $activityId) {
            // Before the start it simply leaves; after it, it stays recorded as committed and taken out.
            $started ? $this->membership->markRemoved($sprintId, $activityId, $by) : $this->membership->deleteFrom($sprintId, $activityId);
        }
        $skipped = $this->addActivities($sprintId, array_diff($wanted, $current), $by, $today);
        $this->afterMembershipChange($sprintId, $today);

        return ['skipped' => $skipped];
    }

    /**
     * Closes the sprint with the wizard's decisions, all in one transaction:
     * terminadas stay as terminadas, each pending activity is moved to the
     * next sprint, returned to the backlog or cancelled (with its reason), and
     * the snapshot is saved.
     *
     * @param array<int, array{action?:string, reason?:string}> $decisions activity id => decision
     * @param array{mode:string, id?:int, data?:array}|null $target next sprint: existing open one or a new one
     * @return array{errors:string[], closure_id?:int, next_sprint_id?:?int}
     */
    public function closeSprint(int $sprintId, array $decisions, ?array $target, ?int $closedBy, ?string $today = null): array
    {
        $today ??= date('Y-m-d');
        $sprint = $this->sprintModel->find($sprintId);
        if ($sprint === null || !SprintClose::isEditable($sprint)) {
            return ['errors' => ['El sprint ya está cerrado.']];
        }

        $active = $this->membership->activeForSprint($sprintId);
        $pending = array_values(array_filter($active, fn (array $a) => !SprintClose::isFinished($a['status_code']) && $a['status_code'] !== Progress::CANCELLED));
        $pendingIds = array_map(fn (array $a) => (int) $a['id'], $pending);
        $decisions = array_intersect_key($decisions, array_flip($pendingIds));

        $needsTarget = SprintClose::needsTarget($decisions);
        $hasTarget = $target !== null && (($target['mode'] ?? '') === 'new'
            || (($target['mode'] ?? '') === 'existing' && $this->isOpenTarget((int) ($target['id'] ?? 0), $sprintId)));
        $errors = SprintClose::validate($pendingIds, $decisions, $hasTarget);
        if ($errors !== []) {
            return ['errors' => $errors];
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $progress = round(Progress::byHours($active), 1);
            $nextId = null;
            if ($needsTarget) {
                $nextId = $target['mode'] === 'new'
                    ? $this->createSprint($target['data'], [], [], $closedBy, $today)['id']
                    : (int) $target['id'];
            }

            $cancelledStatusId = $this->statusId(Progress::CANCELLED);
            $touchedBacklogs = [];
            foreach ($active as $a) {
                $activityId = (int) $a['id'];
                if (SprintClose::isFinished($a['status_code'])) {
                    $this->membership->setOutcome($sprintId, $activityId, 'completed');
                } elseif ($a['status_code'] === Progress::CANCELLED) {
                    $this->membership->setOutcome($sprintId, $activityId, 'cancelled', 'Cancelada durante el sprint');
                } else {
                    $action = $decisions[$activityId]['action'];
                    $reason = $action === SprintClose::CANCEL ? trim((string) $decisions[$activityId]['reason']) : null;
                    $this->membership->setOutcome($sprintId, $activityId, SprintClose::OUTCOME[$action], $reason);
                    if ($action === SprintClose::MOVE) {
                        $next = $this->sprintModel->find($nextId);
                        // Keeps its id, history, responsable and %; it is labeled "Arrastrada desde" this sprint.
                        $origin = SprintScope::hasStarted($next['start_date'], $today) ? SprintScope::ADDED : SprintScope::CARRIED;
                        $this->membership->add($nextId, $activityId, $origin, $closedBy, $sprintId);
                    } elseif ($action === SprintClose::CANCEL) {
                        $this->updateActivityStatus($activityId, $cancelledStatusId);
                        $touchedBacklogs[] = (int) $a['backlog_item_id'];
                    }
                }
            }

            if ($nextId !== null) {
                // The next sprint also takes the projects of the activities moved into it.
                $moved = array_filter($pending, fn (array $a) => $decisions[(int) $a['id']]['action'] === SprintClose::MOVE);
                $this->sprintModel->syncProjects($nextId, array_unique(array_merge(
                    $this->sprintModel->projectIds($nextId),
                    array_map(fn (array $a) => (int) $a['project_id'], $moved)
                )));
                $this->afterMembershipChange($nextId, $today);
            }

            $summary = SprintScope::summary($this->membership->forSprint($sprintId));
            $closureId = $this->closureModel->insert([
                'sprint_id' => $sprintId,
                'closed_by' => $closedBy,
                'committed_count' => $summary['committed_count'],
                'committed_hours' => $summary['committed_hours'],
                'committed_from_legacy' => $summary['committed_from_legacy'] ? 1 : 0,
                'added_count' => $summary['added_count'],
                'added_hours' => $summary['added_hours'],
                'added_completed_count' => $summary['added_completed_count'],
                'completed_count' => $summary['completed_count'],
                'completed_hours' => $summary['completed_hours'],
                'committed_completed_hours' => $summary['committed_completed_hours'],
                'moved_count' => $summary['moved_count'],
                'returned_count' => $summary['returned_count'],
                'cancelled_count' => $summary['cancelled_count'],
                'removed_count' => $summary['removed_count'],
                'progress_percent' => $progress,
                'compliance_percent' => $summary['compliance_percent'],
                'next_sprint_id' => $nextId,
            ]);

            $this->sprintModel->update($sprintId, [
                'status' => 'closed',
                'completion_percent' => $progress,
                'compliance_percent' => $summary['compliance_percent'],
            ]);

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        if ($touchedBacklogs !== []) {
            (new StatusSyncService())->sync(array_unique($touchedBacklogs));
        }

        return ['errors' => [], 'closure_id' => $closureId, 'next_sprint_id' => $nextId];
    }

    /**
     * Reopens a closed sprint. The reason is mandatory; the snapshot of the
     * close is kept (marked as reopened) and closing again saves a new one.
     * What was moved, returned or cancelled at the close is not undone.
     */
    public function reopenSprint(int $sprintId, string $reason): bool
    {
        $sprint = $this->sprintModel->find($sprintId);
        $reason = trim($reason);
        if ($sprint === null || $sprint['status'] !== 'closed' || $reason === '') {
            return false;
        }

        $last = $this->closureModel->forSprint($sprintId)[0] ?? null;
        if ($last !== null) {
            $this->closureModel->markReopened((int) $last['id'], $reason);
        }
        $this->membership->clearCompletedOutcomes($sprintId);
        $this->sprintModel->update($sprintId, ['status' => 'open', 'completion_percent' => null, 'compliance_percent' => null]);

        return true;
    }

    /** Only open sprints can be deleted; a closed one is history. */
    public function deleteSprint(int $sprintId): bool
    {
        $sprint = $this->sprintModel->find($sprintId);
        if ($sprint === null || !SprintClose::isEditable($sprint)) {
            return false;
        }

        return $this->sprintModel->delete($sprintId);
    }

    /**
     * @param int[] $activityIds
     * @return string[] names of the activities skipped because they are in another open sprint
     */
    private function addActivities(int $sprintId, array $activityIds, ?int $by, ?string $today, ?string $origin = null): array
    {
        $today ??= date('Y-m-d');
        $sprint = $this->sprintModel->find($sprintId);
        $busy = $this->membership->inOtherOpenSprint($activityIds, $sprintId);
        $origin ??= SprintScope::originFor($sprint['start_date'], $today);
        $activityModel = new Activity();
        $skipped = [];

        foreach (array_unique(array_map('intval', $activityIds)) as $activityId) {
            if ($activityId <= 0) {
                continue;
            }
            if (isset($busy[$activityId])) {
                $skipped[] = ($activityModel->find($activityId)['name'] ?? ('#' . $activityId)) . ' (está en ' . $busy[$activityId]['sprint_name'] . ')';
                continue;
            }
            $this->membership->add($sprintId, $activityId, $origin, $by);
        }

        return $skipped;
    }

    /** Keeps sprint_backlog in line and, until the start, the committed hours. */
    private function afterMembershipChange(int $sprintId, ?string $today): void
    {
        $today ??= date('Y-m-d');
        $this->membership->syncBacklogs($sprintId);

        $sprint = $this->sprintModel->find($sprintId);
        if (!SprintScope::hasStarted($sprint['start_date'], $today)) {
            $this->sprintModel->update($sprintId, ['committed_hours' => $this->membership->commitHours($sprintId)]);
        }
    }

    private function isOpenTarget(int $targetId, int $sprintId): bool
    {
        $target = $targetId > 0 && $targetId !== $sprintId ? $this->sprintModel->find($targetId) : null;

        return $target !== null && $target['status'] === 'open';
    }

    private function statusId(string $code): int
    {
        foreach ((new Catalog('cat_activity_statuses'))->all() as $s) {
            if ($s['code'] === $code) {
                return (int) $s['id'];
            }
        }

        throw new \RuntimeException("Activity status '$code' not found");
    }

    /** Status change only: the % de avance the activity had is kept. */
    private function updateActivityStatus(int $activityId, int $statusId): void
    {
        (new Activity())->update($activityId, ['status_id' => $statusId]);
    }

    private function rowFrom(array $data): array
    {
        $start = $data['start_date'] ?: date('Y-m-d');
        $end = !empty($data['end_date']) && $data['end_date'] >= $start
            ? $data['end_date']
            : date('Y-m-d', strtotime($start . ' +14 days'));

        return [
            'name' => $data['name'] ?: null,
            'goal' => ($data['goal'] ?? null) ?: null,
            'start_date' => $start,
            'end_date' => $end,
            'duration_weeks' => SprintClose::weeksFor($start, $end),
            'process_owner' => ($data['process_owner'] ?? null) ?: null,
            'notes' => ($data['notes'] ?? null) ?: null,
        ];
    }
}
