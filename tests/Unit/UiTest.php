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
}
