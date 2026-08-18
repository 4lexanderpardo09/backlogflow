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

    public function testDaysLateOnlyCountsPastDueDates(): void
    {
        $this->assertSame(0, DateMath::daysLate('2026-09-01', '2026-08-14'));
        $this->assertSame(4, DateMath::daysLate('2026-08-10', '2026-08-14'));
    }
}
