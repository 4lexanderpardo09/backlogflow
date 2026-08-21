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

    public function testCompletionPercentCountsOnlyCompletedStatus(): void
    {
        // 9 of 10 committed backlog items completed -> 90%, matching the
        // user's own worked example ("si hay 10 y cumplimos 9, fue el 90%").
        $items = array_merge(
            array_fill(0, 9, ['status_code' => 'completed']),
            [['status_code' => 'in_development']]
        );

        $this->assertSame(90.0, SprintProgress::completionPercent($items));
    }

    public function testCompletionPercentAllDone(): void
    {
        $items = array_fill(0, 4, ['status_code' => 'completed']);

        $this->assertSame(100.0, SprintProgress::completionPercent($items));
    }

    public function testCompletionPercentNoneDone(): void
    {
        $items = [['status_code' => 'pending'], ['status_code' => 'blocked']];

        $this->assertSame(0.0, SprintProgress::completionPercent($items));
    }
}
