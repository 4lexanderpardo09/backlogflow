<?php

namespace App\Helpers;

/**
 * How late a backlog / project / sprint is, from its activities:
 * the number of overdue activities (not terminada nor cancelada, due date
 * already passed) and the days of delay = today − the oldest due date among
 * the activities that are not finished yet (0 when that date hasn't passed).
 */
class Delay
{
    /**
     * @param array<int, array{due_date: ?string, status_code: string}> $activities
     * @return array{overdue_count:int, days_late:int, oldest_due:?string}
     */
    public static function fromActivities(array $activities, string $today): array
    {
        $oldest = null;
        $overdue = 0;

        foreach ($activities as $a) {
            if (in_array($a['status_code'], ['completed', Progress::CANCELLED], true) || empty($a['due_date'])) {
                continue;
            }
            if ($oldest === null || $a['due_date'] < $oldest) {
                $oldest = $a['due_date'];
            }
            if ($a['due_date'] < $today) {
                $overdue++;
            }
        }

        return [
            'overdue_count' => $overdue,
            'days_late' => self::daysLate($oldest, $today),
            'oldest_due' => $oldest,
        ];
    }

    /** today − oldest due date of the unfinished activities, never negative. */
    public static function daysLate(?string $oldestDue, string $today): int
    {
        return $oldestDue === null ? 0 : max(0, DateMath::daysBetween($oldestDue, $today));
    }

    /**
     * Oldest of several due dates (a platform's sub-projects), ignoring empty ones.
     *
     * @param array<int, ?string> $dates
     */
    public static function oldest(array $dates): ?string
    {
        $dates = array_filter($dates, fn ($d) => !empty($d));

        return $dates === [] ? null : min($dates);
    }
}
