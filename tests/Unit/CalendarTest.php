<?php

namespace Tests\Unit;

use App\Helpers\Calendar;
use PHPUnit\Framework\TestCase;

class CalendarTest extends TestCase
{
    public function testMonthWeeksStartOnMondayAndCoverTheMonth(): void
    {
        // October 2026 starts on a Thursday and ends on a Saturday.
        $weeks = Calendar::monthWeeks(2026, 10);

        $this->assertCount(5, $weeks);
        $this->assertSame('2026-09-28', $weeks[0]['start']);
        $this->assertSame('2026-09-28', $weeks[0]['days'][0]);
        $this->assertSame('2026-10-04', $weeks[0]['days'][6]);
        $this->assertSame('2026-11-01', $weeks[4]['days'][6]);
    }

    public function testMonthWeeksForAMonthStartingOnMonday(): void
    {
        $weeks = Calendar::monthWeeks(2026, 6); // June 2026 starts on Monday

        $this->assertSame('2026-06-01', $weeks[0]['start']);
        $this->assertCount(5, $weeks);
    }

    public function testLayoutWeekClipsToTheWeekAndFlagsContinuation(): void
    {
        $segments = Calendar::layoutWeek('2026-10-12', [
            ['id' => 1, 'start' => '2026-10-08', 'end' => '2026-10-13'],
            ['id' => 2, 'start' => '2026-10-17', 'end' => '2026-10-21'],
            ['id' => 3, 'start' => '2026-10-30', 'end' => '2026-10-31'],
        ]);

        $this->assertCount(2, $segments);
        $this->assertSame(['col' => 0, 'span' => 2, 'continues_before' => true, 'continues_after' => false],
            array_intersect_key($segments[0], array_flip(['col', 'span', 'continues_before', 'continues_after'])));
        $this->assertSame(['col' => 5, 'span' => 2, 'continues_before' => false, 'continues_after' => true],
            array_intersect_key($segments[1], array_flip(['col', 'span', 'continues_before', 'continues_after'])));
    }

    public function testLayoutWeekStacksOverlapsAndReusesFreeLanes(): void
    {
        $segments = Calendar::layoutWeek('2026-10-12', [
            ['id' => 'a', 'start' => '2026-10-12', 'end' => '2026-10-14'],
            ['id' => 'b', 'start' => '2026-10-13', 'end' => '2026-10-13'],
            ['id' => 'c', 'start' => '2026-10-15', 'end' => '2026-10-16'],
        ]);

        $lanes = array_column(array_map(fn ($s) => ['id' => $s['event']['id'], 'lane' => $s['lane']], $segments), 'lane', 'id');
        $this->assertSame(['a' => 0, 'b' => 1, 'c' => 0], $lanes);
    }

    public function testHiddenPerDayCountsSegmentsBeyondTheVisibleLanes(): void
    {
        $events = [];
        for ($i = 0; $i < 5; $i++) {
            $events[] = ['id' => $i, 'start' => '2026-10-13', 'end' => '2026-10-14'];
        }

        $hidden = Calendar::hiddenPerDay(Calendar::layoutWeek('2026-10-12', $events), 3);

        $this->assertSame([0, 2, 2, 0, 0, 0, 0], $hidden);
    }
}
