<?php

namespace Tests\Unit;

use App\Helpers\Progress;
use PHPUnit\Framework\TestCase;

class ProgressTest extends TestCase
{
    public function testBacklogProgressAveragesActivities(): void
    {
        $this->assertEquals(50.0, Progress::backlogProgress([100, 100, 50, 0, 0]));
    }

    public function testBacklogProgressWithNoActivitiesIsZero(): void
    {
        $this->assertSame(0.0, Progress::backlogProgress([]));
    }

    public function testProjectProgressWeightsByActivityCount(): void
    {
        // Matches the worked example from the spec: backlog 1 has 3 activities
        // averaging 83.33%, backlog 2 has 2 activities averaging 0%.
        $backlogs = [
            ['progress_percent' => 100.0, 'activity_count' => 3], // simplified: treat as already-averaged
            ['progress_percent' => 0.0, 'activity_count' => 2],
        ];

        // Weighted average: (100*3 + 0*2) / 5 = 60
        $this->assertEquals(60.0, Progress::projectProgress($backlogs));
    }

    public function testProjectProgressWithNoActivitiesIsZero(): void
    {
        $this->assertSame(0.0, Progress::projectProgress([
            ['progress_percent' => 50.0, 'activity_count' => 0],
        ]));
    }

    public function testProjectProgressMatchesSpecExample(): void
    {
        // Backlog 1: activities 100, 100, 50 -> avg 83.33..., 3 activities
        // Backlog 2: activities 0, 0 -> avg 0, 2 activities
        $backlog1Avg = Progress::backlogProgress([100, 100, 50]);
        $backlog2Avg = Progress::backlogProgress([0, 0]);

        $projectProgress = Progress::projectProgress([
            ['progress_percent' => $backlog1Avg, 'activity_count' => 3],
            ['progress_percent' => $backlog2Avg, 'activity_count' => 2],
        ]);

        // (83.333*3 + 0*2) / 5 = 50
        $this->assertEqualsWithDelta(50.0, $projectProgress, 0.01);
    }
}
