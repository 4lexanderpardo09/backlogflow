<?php

namespace App\Models;

use App\Core\Model;

class Sprint extends Model
{
    protected string $table = 'sprints';

    public function byProject(int $projectId): array
    {
        return $this->fetchAll(
            'SELECT * FROM sprints WHERE project_id = :project_id ORDER BY sequence_number DESC',
            ['project_id' => $projectId]
        );
    }

    public function openSprintForProject(int $projectId): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM sprints WHERE project_id = :project_id AND status = 'open' ORDER BY sequence_number DESC LIMIT 1",
            ['project_id' => $projectId]
        );
    }

    /** Every open sprint across all projects, for the backlog form's sprint picker. */
    public function allOpenWithProject(): array
    {
        return $this->fetchAll(
            "SELECT s.*, p.name AS project_name
             FROM sprints s JOIN projects p ON p.id = s.project_id
             WHERE s.status = 'open'
             ORDER BY p.name ASC, s.sequence_number DESC"
        );
    }

    public function findWithProject(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT s.*, p.name AS project_name
             FROM sprints s JOIN projects p ON p.id = s.project_id
             WHERE s.id = :id',
            ['id' => $id]
        );
    }

    public function lastSequenceNumber(int $projectId): int
    {
        $row = $this->fetchOne(
            'SELECT MAX(sequence_number) AS max_seq FROM sprints WHERE project_id = :project_id',
            ['project_id' => $projectId]
        );

        return (int) ($row['max_seq'] ?? 0);
    }
}
