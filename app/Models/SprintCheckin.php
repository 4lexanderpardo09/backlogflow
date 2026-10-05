<?php

namespace App\Models;

use App\Core\Model;

/**
 * Pure data access for sprint_checkins (weekly follow-up of a sprint).
 */
class SprintCheckin extends Model
{
    protected string $table = 'sprint_checkins';

    /** Newest first, with the name of whoever registered it. */
    public function forSprint(int $sprintId): array
    {
        return $this->fetchAll(
            'SELECT c.*, d.name AS registered_by_name
             FROM sprint_checkins c
             LEFT JOIN developers d ON d.id = c.registered_by
             WHERE c.sprint_id = :id
             ORDER BY c.checkin_date DESC, c.id DESC',
            ['id' => $sprintId]
        );
    }
}
