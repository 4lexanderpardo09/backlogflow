<?php

namespace Tests\Unit;

use App\Helpers\Delay;
use PHPUnit\Framework\TestCase;

class DelayTest extends TestCase
{
    private const TODAY = '2026-10-26';

    public function testDaysLateCountFromTheOldestUnfinishedDueDate(): void
    {
        $delay = Delay::fromActivities([
            ['due_date' => '2026-10-20', 'status_code' => 'pending'],
            ['due_date' => '2026-10-06', 'status_code' => 'in_progress'],
            ['due_date' => '2026-11-10', 'status_code' => 'pending'],
        ], self::TODAY);

        $this->assertSame(['overdue_count' => 2, 'days_late' => 20, 'oldest_due' => '2026-10-06'], $delay);
    }

    public function testFinishedAndCancelledActivitiesAreIgnored(): void
    {
        $delay = Delay::fromActivities([
            ['due_date' => '2026-09-01', 'status_code' => 'completed'],
            ['due_date' => '2026-09-02', 'status_code' => 'cancelled'],
            ['due_date' => '2026-11-01', 'status_code' => 'pending'],
        ], self::TODAY);

        $this->assertSame(0, $delay['overdue_count']);
        $this->assertSame(0, $delay['days_late']);
    }

    public function testNoDueDatesMeansNoDelay(): void
    {
        $this->assertSame(['overdue_count' => 0, 'days_late' => 0, 'oldest_due' => null],
            Delay::fromActivities([['due_date' => null, 'status_code' => 'pending']], self::TODAY));
    }
}
