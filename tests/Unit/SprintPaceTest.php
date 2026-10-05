<?php

namespace Tests\Unit;

use App\Helpers\SprintPace;
use PHPUnit\Framework\TestCase;

class SprintPaceTest extends TestCase
{
    public function testWeeksCoverTheSprintInSevenDayBlocks(): void
    {
        $weeks = SprintPace::weeks('2026-10-05', 2);

        $this->assertCount(2, $weeks);
        $this->assertSame(['number' => 1, 'start' => '2026-10-05', 'end' => '2026-10-11'], $weeks[0]);
        $this->assertSame(['number' => 2, 'start' => '2026-10-12', 'end' => '2026-10-18'], $weeks[1]);
    }

    public function testWeekOfClampsToTheSprintRange(): void
    {
        $this->assertSame(1, SprintPace::weekOf('2026-10-05', 3, '2026-10-01'));
        $this->assertSame(1, SprintPace::weekOf('2026-10-05', 3, '2026-10-11'));
        $this->assertSame(2, SprintPace::weekOf('2026-10-05', 3, '2026-10-12'));
        $this->assertSame(3, SprintPace::weekOf('2026-10-05', 3, '2026-12-30'));
    }

    public function testElapsedPercent(): void
    {
        // 14-day sprint: 2026-10-05 .. 2026-10-18
        $this->assertSame(0.0, SprintPace::elapsedPercent('2026-10-05', '2026-10-18', '2026-10-01'));
        $this->assertSame(50.0, SprintPace::elapsedPercent('2026-10-05', '2026-10-18', '2026-10-11'));
        $this->assertSame(100.0, SprintPace::elapsedPercent('2026-10-05', '2026-10-18', '2026-11-01'));
    }

    public function testHealthComparesProgressWithElapsedTime(): void
    {
        $this->assertSame('green', SprintPace::health(50.0, 45.0));
        $this->assertSame('green', SprintPace::health(50.0, 60.0));
        $this->assertSame('yellow', SprintPace::health(50.0, 30.0));
        $this->assertSame('red', SprintPace::health(80.0, 30.0));
        $this->assertSame('green', SprintPace::health(100.0, 100.0));
    }
}
