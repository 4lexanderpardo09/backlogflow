<?php

namespace Tests\Unit;

use App\Helpers\Gantt;
use PHPUnit\Framework\TestCase;

class GanttTest extends TestCase
{
    private const START = '2026-01-01';
    private const END = '2026-01-31'; // 30-day window

    public function testBarAtWindowStartHasZeroLeft(): void
    {
        [$left, $width] = Gantt::barGeometry(self::START, self::END, '2026-01-01', '2026-01-16');

        $this->assertSame(0.0, $left);
        $this->assertSame(50.0, $width); // 15 of 30 days
    }

    public function testBarInSecondHalfStartsAtFiftyPercent(): void
    {
        [$left, $width] = Gantt::barGeometry(self::START, self::END, '2026-01-16', '2026-01-31');

        $this->assertSame(50.0, $left);
        $this->assertSame(50.0, $width);
    }

    public function testBarIsClampedToTheWindow(): void
    {
        // Starts before the window and ends after it -> fills it entirely.
        [$left, $width] = Gantt::barGeometry(self::START, self::END, '2025-12-01', '2026-03-01');

        $this->assertSame(0.0, $left);
        $this->assertSame(100.0, $width);
    }

    public function testRangeEntirelyOutsideWindowIsEmpty(): void
    {
        $this->assertSame([0.0, 0.0], Gantt::barGeometry(self::START, self::END, '2025-01-01', '2025-06-01'));
    }

    public function testNullDatesDefaultToTheWholeWindow(): void
    {
        $this->assertSame([0.0, 100.0], Gantt::barGeometry(self::START, self::END, null, null));
    }

    public function testRangeTouchingTheWindowOverlaps(): void
    {
        // Ends on the window's first day.
        $this->assertTrue(Gantt::overlapsWindow(self::START, self::END, '2025-12-20', '2026-01-01'));
        // Starts inside and runs past the end.
        $this->assertTrue(Gantt::overlapsWindow(self::START, self::END, '2026-01-20', '2026-02-15'));
    }

    public function testRangeOutsideTheWindowDoesNotOverlap(): void
    {
        $this->assertFalse(Gantt::overlapsWindow(self::START, self::END, '2025-11-01', '2025-12-31'));
        $this->assertFalse(Gantt::overlapsWindow(self::START, self::END, '2026-02-01', '2026-02-10'));
    }

    public function testMissingDatesOverlapLikeTheirBar(): void
    {
        $this->assertTrue(Gantt::overlapsWindow(self::START, self::END, null, null));
    }

    public function testRenderShowsEmptyStateForNoRows(): void
    {
        $this->assertStringContainsString('empty-state', Gantt::render(self::START, self::END, []));
    }

    public function testRenderPlacesABarAndItsFill(): void
    {
        $html = Gantt::render(self::START, self::END, [
            ['label' => 'A', 'kind' => 'activity', 'start' => '2026-01-16', 'end' => '2026-01-31', 'progress' => 40],
        ]);

        $this->assertStringContainsString('left:50.00%', $html);
        $this->assertStringContainsString('width:40.0%', $html); // fill proportional to progress
    }
}
