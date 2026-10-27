<?php

namespace Tests\Unit;

use App\Helpers\Progress;
use PHPUnit\Framework\TestCase;

class ProgressTest extends TestCase
{
    public function testEqualHoursGiveThePlainAverage(): void
    {
        $this->assertEqualsWithDelta(70.0, Progress::byHours([
            ['progress_percent' => 100, 'estimated_hours' => 1],
            ['progress_percent' => 50, 'estimated_hours' => 1],
            ['progress_percent' => 60, 'estimated_hours' => 1],
        ]), 0.001);
    }

    public function testBiggerActivitiesWeighMore(): void
    {
        // The reported case: 100 %, 50 % and 60 % used to give 70 % no matter the size.
        // With 1 h, 8 h and 1 h it is (100·1 + 50·8 + 60·1) / 10 = 56 %.
        $this->assertEqualsWithDelta(56.0, Progress::byHours([
            ['progress_percent' => 100, 'estimated_hours' => 1],
            ['progress_percent' => 50, 'estimated_hours' => 8],
            ['progress_percent' => 60, 'estimated_hours' => 1],
        ]), 0.001);
    }

    public function testCancelledActivitiesDoNotCount(): void
    {
        $this->assertSame(100.0, Progress::byHours([
            ['progress_percent' => 100, 'estimated_hours' => 2, 'status_code' => 'completed'],
            ['progress_percent' => 0, 'estimated_hours' => 5, 'status_code' => 'cancelled'],
        ]));
    }

    public function testMissingHoursCountAsOne(): void
    {
        $this->assertEqualsWithDelta(50.0, Progress::byHours([
            ['progress_percent' => 100],
            ['progress_percent' => 0, 'estimated_hours' => null],
        ]), 0.001);
    }

    public function testNoActivitiesIsZero(): void
    {
        $this->assertSame(0.0, Progress::byHours([]));
        $this->assertSame(0.0, Progress::byHours([['progress_percent' => 80, 'status_code' => 'cancelled']]));
    }

    public function testRollUpEqualsTheCalculationOverAllActivities(): void
    {
        // Backlog A: 100 % over 2 h; backlog B: 0 % over 6 h -> project 25 %, not the 50 % a plain average of backlogs gives.
        $a = [['progress_percent' => 100, 'estimated_hours' => 2]];
        $b = [['progress_percent' => 0, 'estimated_hours' => 6]];

        $rolled = Progress::rollUp([
            ['progress_percent' => Progress::byHours($a), 'hours_total' => 2],
            ['progress_percent' => Progress::byHours($b), 'hours_total' => 6],
        ]);

        $this->assertEqualsWithDelta(25.0, $rolled, 0.001);
        $this->assertEqualsWithDelta(Progress::byHours([...$a, ...$b]), $rolled, 0.001);
    }

    public function testRollUpWithNoHoursIsZero(): void
    {
        $this->assertSame(0.0, Progress::rollUp([['progress_percent' => 50, 'hours_total' => 0]]));
    }
}
