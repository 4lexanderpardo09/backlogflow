<?php

namespace Tests\Unit;

use App\Helpers\SprintProgress;
use PHPUnit\Framework\TestCase;

class SprintProgressTest extends TestCase
{
    public function testCompletionPercentWithNoItemsIsZero(): void
    {
        $this->assertSame(0.0, SprintProgress::completionPercent([]));
    }

    public function testCompletionPercentIsActivityWeightedAverage(): void
    {
        // Backlog A: 100% over 1 activity; Backlog B: 0% over 3 activities.
        // Weighted: (100*1 + 0*3) / 4 = 25.0 — NOT 50, which a plain average
        // would give. A lonely finished task can't fake sprint progress.
        $items = [
            ['progress_percent' => 100, 'activity_count' => 1],
            ['progress_percent' => 0, 'activity_count' => 3],
        ];

        $this->assertSame(25.0, SprintProgress::completionPercent($items));
    }

    public function testCompletionPercentAllDone(): void
    {
        $items = [
            ['progress_percent' => 100, 'activity_count' => 2],
            ['progress_percent' => 100, 'activity_count' => 5],
        ];

        $this->assertSame(100.0, SprintProgress::completionPercent($items));
    }

    public function testCompletionPercentIgnoresBacklogsWithNoActivities(): void
    {
        $items = [
            ['progress_percent' => 0, 'activity_count' => 0],
            ['progress_percent' => 60, 'activity_count' => 2],
        ];

        $this->assertSame(60.0, SprintProgress::completionPercent($items));
    }

    public function testCompletionPercentRoundsToOneDecimal(): void
    {
        $items = [
            ['progress_percent' => 100, 'activity_count' => 1],
            ['progress_percent' => 0, 'activity_count' => 2],
        ];

        // 100/3 = 33.333... -> 33.3
        $this->assertSame(33.3, SprintProgress::completionPercent($items));
    }
}
