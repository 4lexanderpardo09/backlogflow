<?php

namespace Tests\Unit;

use App\Helpers\DateMath;
use PHPUnit\Framework\TestCase;

class DateMathTest extends TestCase
{
    public function testDaysBetweenFuture(): void
    {
        $this->assertSame(10, DateMath::daysBetween('2026-08-14', '2026-08-24'));
    }

    public function testDaysBetweenPast(): void
    {
        $this->assertSame(-5, DateMath::daysBetween('2026-08-14', '2026-08-09'));
    }

    public function testDaysElapsedNeverNegative(): void
    {
        $this->assertSame(0, DateMath::daysElapsed('2026-09-01', '2026-08-14'));
        $this->assertSame(14, DateMath::daysElapsed('2026-07-31', '2026-08-14'));
    }

    public function testDaysRemainingNullWhenNoDueDate(): void
    {
        $this->assertNull(DateMath::daysRemaining(null, '2026-08-14'));
    }

    public function testRangeOverlapsWithoutFilterAlwaysMatches(): void
    {
        $this->assertTrue(DateMath::rangeOverlaps(null, null, null, null));
    }

    public function testRangeOverlapsWhenRangesCross(): void
    {
        // Activity 10–20 Sep vs filter 15–30 Sep.
        $this->assertTrue(DateMath::rangeOverlaps('2026-09-10', '2026-09-20', '2026-09-15', '2026-09-30'));
        // Only "desde": anything ending on or after it.
        $this->assertTrue(DateMath::rangeOverlaps('2026-09-10', '2026-09-20', '2026-09-20', null));
    }

    public function testRangeOutsideTheFilterDoesNotMatch(): void
    {
        $this->assertFalse(DateMath::rangeOverlaps('2026-08-01', '2026-08-31', '2026-09-01', '2026-09-30'));
        $this->assertFalse(DateMath::rangeOverlaps('2026-10-01', '2026-10-05', null, '2026-09-30'));
    }

    public function testUndatedRecordNeverMatchesAnActiveDateFilter(): void
    {
        $this->assertFalse(DateMath::rangeOverlaps(null, null, '2026-09-01', '2026-09-30'));
    }

    public function testMissingEndBorrowsTheStart(): void
    {
        $this->assertTrue(DateMath::rangeOverlaps('2026-09-12', null, '2026-09-10', '2026-09-15'));
    }

    public function testDaysLateOnlyCountsPastDueDates(): void
    {
        $this->assertSame(0, DateMath::daysLate('2026-09-01', '2026-08-14'));
        $this->assertSame(4, DateMath::daysLate('2026-08-10', '2026-08-14'));
    }
}
