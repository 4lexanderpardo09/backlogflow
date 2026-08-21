<?php

namespace App\Services\Projects;

use App\Helpers\DateMath;
use App\Helpers\SprintProgress;
use App\Models\BacklogItem;
use App\Models\Project;
use App\Models\Sprint;

/**
 * Orchestrates the sprint cycle: opening the next N-day review sprint for a
 * project (N = projects.sprint_duration_days) and closing one, which
 * records its completion % and rolls unfinished backlog items into the
 * next sprint automatically.
 */
class SprintService
{
    public function __construct(
        private readonly Sprint $sprintModel = new Sprint(),
        private readonly Project $projectModel = new Project(),
        private readonly BacklogItem $backlogModel = new BacklogItem(),
    ) {
    }

    /** Opens the next sprint for a project, starting today, ending sprint_duration_days later. */
    public function openSprint(int $projectId, ?string $processOwner = null, ?string $today = null): int
    {
        $today ??= date('Y-m-d');
        $project = $this->projectModel->find($projectId);
        $durationDays = (int) ($project['sprint_duration_days'] ?? 8);

        $nextSequence = $this->sprintModel->lastSequenceNumber($projectId) + 1;
        $endDate = date('Y-m-d', strtotime($today . " +{$durationDays} days"));

        return $this->sprintModel->insert([
            'project_id' => $projectId,
            'sequence_number' => $nextSequence,
            'start_date' => $today,
            'end_date' => $endDate,
            'status' => 'open',
            'process_owner' => $processOwner ?: null,
        ]);
    }

    /**
     * Closes a sprint: computes its completion %, opens the next sprint for
     * the same project, and rolls every unfinished backlog item into it.
     */
    public function closeSprint(int $sprintId, ?string $today = null): void
    {
        $sprint = $this->sprintModel->find($sprintId);
        if ($sprint === null || $sprint['status'] === 'closed') {
            return;
        }

        $items = $this->backlogModel->bySprint($sprintId);
        $completion = SprintProgress::completionPercent($items);

        $this->sprintModel->update($sprintId, [
            'status' => 'closed',
            'completion_percent' => $completion,
        ]);

        $nextSprintId = $this->openSprint((int) $sprint['project_id'], $sprint['process_owner'], $today);
        $this->backlogModel->rollUnfinishedToSprint($sprintId, $nextSprintId);
    }
}
