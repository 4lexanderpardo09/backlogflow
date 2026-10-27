<?php

namespace Tests\Unit;

use App\Helpers\Ui;
use PHPUnit\Framework\TestCase;

class UiTest extends TestCase
{
    public function testOverdueSaysHowLateItIs(): void
    {
        $this->assertStringContainsString('14 días de atraso', Ui::daysRemainingLabel(-14));
    }

    /** The number alone doesn't say against which date it is counting. */
    public function testDeadlineIsNamedInTheTooltip(): void
    {
        $html = Ui::daysRemainingLabel(-14, false, '2026-09-15');

        $this->assertStringContainsString('title="Fecha estimada de fin: 15/09/2026"', $html);
        $this->assertStringContainsString('14 días de atraso', $html);
    }

    public function testTooltipAlsoOnDueSoonAndPlainCounts(): void
    {
        $this->assertStringContainsString('title=', Ui::daysRemainingLabel(3, false, '2026-10-02'));
        $this->assertStringContainsString('title=', Ui::daysRemainingLabel(45, false, '2026-11-13'));
    }

    public function testNoTooltipWhenNoDeadlineIsGiven(): void
    {
        $this->assertStringNotContainsString('title=', Ui::daysRemainingLabel(-14));
        $this->assertStringNotContainsString('title=', Ui::daysRemainingLabel(45));
    }

    public function testCompletedAndUndefinedIgnoreTheDeadline(): void
    {
        $this->assertStringNotContainsString('atraso', Ui::daysRemainingLabel(-14, true, '2026-09-15'));
        $this->assertStringNotContainsString('title=', Ui::daysRemainingLabel(null, false, '2026-09-15'));
    }

    public function testDelayTagShowsOverdueCountAndDaysLate(): void
    {
        $html = Ui::delayTag(3, '2026-10-14', '2026-10-26');

        $this->assertStringContainsString('3 vencidas', $html);
        $this->assertStringContainsString('12 días de atraso', $html);
    }

    public function testDelayTagIsEmptyWhenNothingIsOverdue(): void
    {
        $this->assertSame('', Ui::delayTag(0, '2026-10-14', '2026-10-26'));
    }

    public function testDelayTagSingular(): void
    {
        $this->assertStringContainsString('1 vencida', Ui::delayTag(1, '2026-10-25', '2026-10-26'));
        $this->assertStringContainsString('1 día de atraso', Ui::delayTag(1, '2026-10-25', '2026-10-26'));
    }
}
