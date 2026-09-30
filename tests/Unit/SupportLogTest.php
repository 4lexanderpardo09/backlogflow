<?php

namespace Tests\Unit;

use App\Helpers\SupportLog;
use PHPUnit\Framework\TestCase;

class SupportLogTest extends TestCase
{
    public function testFormatMinutes(): void
    {
        $this->assertSame('0 min', SupportLog::formatMinutes(0));
        $this->assertSame('45 min', SupportLog::formatMinutes(45));
        $this->assertSame('1 h', SupportLog::formatMinutes(60));
        $this->assertSame('1 h 30 min', SupportLog::formatMinutes(90));
        $this->assertSame('0 min', SupportLog::formatMinutes(-5));
    }

    public function testSummarizeEmpty(): void
    {
        $this->assertSame(
            ['total' => 0, 'helpdesk' => 0, 'outside_helpdesk' => 0, 'outside_pct' => 0.0, 'total_minutes' => 0, 'avg_minutes' => 0],
            SupportLog::summarize([])
        );
    }

    public function testSummarizeCountsHelpdeskAndTime(): void
    {
        $summary = SupportLog::summarize([
            ['in_helpdesk' => '1', 'time_minutes' => '30'],
            ['in_helpdesk' => '0', 'time_minutes' => '15'],
            ['in_helpdesk' => '0', 'time_minutes' => '45'],
        ]);

        $this->assertSame(3, $summary['total']);
        $this->assertSame(1, $summary['helpdesk']);
        $this->assertSame(2, $summary['outside_helpdesk']);
        $this->assertSame(66.7, $summary['outside_pct']);
        $this->assertSame(90, $summary['total_minutes']);
        $this->assertSame(30, $summary['avg_minutes']);
    }
}
