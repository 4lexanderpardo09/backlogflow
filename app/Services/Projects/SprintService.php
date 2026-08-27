<?php

namespace App\Services\Projects;

use App\Models\Sprint;

/**
 * Create / edit / close a manually managed sprint. A sprint spans any
 * number of child projects (sprint_project) and pulls backlog items from
 * any of them (sprint_backlog); its end date is derived from the start
 * date plus the duration in weeks. Closing it freezes the completion %.
 */
class SprintService
{
    public function __construct(
        private readonly Sprint $sprintModel = new Sprint(),
    ) {
    }

    /**
     * @param array{name:?string,start_date:string,duration_weeks:int,process_owner:?string,notes:?string} $data
     * @param int[] $projectIds
     * @param int[] $backlogIds
     */
    public function createSprint(array $data, array $projectIds, array $backlogIds): int
    {
        $sprintId = $this->sprintModel->insert($this->rowFrom($data) + ['status' => 'open']);
        $this->sprintModel->syncProjects($sprintId, $projectIds);
        $this->sprintModel->syncBacklogs($sprintId, $backlogIds);

        return $sprintId;
    }

    /**
     * @param array{name:?string,start_date:string,duration_weeks:int,process_owner:?string,notes:?string} $data
     * @param int[] $projectIds
     * @param int[] $backlogIds
     */
    public function updateSprint(int $sprintId, array $data, array $projectIds, array $backlogIds): void
    {
        $this->sprintModel->update($sprintId, $this->rowFrom($data));
        $this->sprintModel->syncProjects($sprintId, $projectIds);
        $this->sprintModel->syncBacklogs($sprintId, $backlogIds);
    }

    /** Closes a sprint and freezes its completion %. No roll-over. */
    public function closeSprint(int $sprintId): void
    {
        $sprint = $this->sprintModel->find($sprintId);
        if ($sprint === null || $sprint['status'] === 'closed') {
            return;
        }

        $this->sprintModel->update($sprintId, [
            'status' => 'closed',
            'completion_percent' => $this->sprintModel->completionFor($sprintId),
        ]);
    }

    /** Reopens a closed sprint (clears the frozen %). */
    public function reopenSprint(int $sprintId): void
    {
        $this->sprintModel->update($sprintId, ['status' => 'open', 'completion_percent' => null]);
    }

    private function rowFrom(array $data): array
    {
        $weeks = max(1, (int) $data['duration_weeks']);
        $start = $data['start_date'] ?: date('Y-m-d');

        return [
            'name' => $data['name'] ?: null,
            'start_date' => $start,
            'duration_weeks' => $weeks,
            'end_date' => date('Y-m-d', strtotime($start . ' +' . ($weeks * 7) . ' days')),
            'process_owner' => $data['process_owner'] ?: null,
            'notes' => $data['notes'] ?: null,
        ];
    }
}
