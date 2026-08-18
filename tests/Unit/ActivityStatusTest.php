<?php

namespace Tests\Unit;

use App\Helpers\ActivityStatus;
use PHPUnit\Framework\TestCase;

class ActivityStatusTest extends TestCase
{
    private const TODAY = '2026-08-14';

    public function testFullProgressIsCompleted(): void
    {
        $this->assertSame(ActivityStatus::COMPLETED, ActivityStatus::compute(100, '2026-08-01', false, self::TODAY));
    }

    public function testPastDueWithPartialProgressIsOverdue(): void
    {
        $this->assertSame(ActivityStatus::OVERDUE, ActivityStatus::compute(50, '2026-08-10', false, self::TODAY));
    }

    public function testZeroProgressNotStartedIsPending(): void
    {
        $this->assertSame(ActivityStatus::PENDING, ActivityStatus::compute(0, '2026-09-01', false, self::TODAY));
    }

    public function testPartialProgressBeforeDueDateIsInProgress(): void
    {
        $this->assertSame(ActivityStatus::IN_PROGRESS, ActivityStatus::compute(40, '2026-09-01', false, self::TODAY));
    }

    public function testUnresolvedDependencyIsBlockedRegardlessOfDueDate(): void
    {
        $this->assertSame(ActivityStatus::BLOCKED, ActivityStatus::compute(0, '2026-07-01', true, self::TODAY));
    }

    public function testCompletedActivityIgnoresUnresolvedDependencyFlag(): void
    {
        $this->assertSame(ActivityStatus::COMPLETED, ActivityStatus::compute(100, '2026-07-01', true, self::TODAY));
    }

    public function testIsDueSoonWithinWindow(): void
    {
        $this->assertTrue(ActivityStatus::isDueSoon(60, '2026-08-18', 5, self::TODAY));
        $this->assertFalse(ActivityStatus::isDueSoon(60, '2026-09-01', 5, self::TODAY));
        $this->assertFalse(ActivityStatus::isDueSoon(100, '2026-08-15', 5, self::TODAY));
    }
}
