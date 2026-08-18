<?php

namespace Tests\Unit;

use App\Helpers\ContractAlert;
use PHPUnit\Framework\TestCase;

class ContractAlertTest extends TestCase
{
    private const TODAY = '2026-08-14';

    public function testExpiredContract(): void
    {
        $this->assertSame(ContractAlert::EXPIRED, ContractAlert::bucket('2026-07-20', self::TODAY));
    }

    public function testLessThan30Days(): void
    {
        $this->assertSame(ContractAlert::LT_30, ContractAlert::bucket('2026-09-01', self::TODAY));
    }

    public function testBetween30And60Days(): void
    {
        $this->assertSame(ContractAlert::D30_60, ContractAlert::bucket('2026-10-01', self::TODAY));
    }

    public function testBetween60And90Days(): void
    {
        $this->assertSame(ContractAlert::D60_90, ContractAlert::bucket('2026-11-01', self::TODAY));
    }

    public function testMoreThan90Days(): void
    {
        $this->assertSame(ContractAlert::GT_90, ContractAlert::bucket('2027-01-01', self::TODAY));
    }

    public function testSeverityMapping(): void
    {
        $this->assertSame('red', ContractAlert::severity(ContractAlert::EXPIRED));
        $this->assertSame('red', ContractAlert::severity(ContractAlert::LT_30));
        $this->assertSame('yellow', ContractAlert::severity(ContractAlert::D30_60));
        $this->assertSame('green', ContractAlert::severity(ContractAlert::D60_90));
        $this->assertSame('green', ContractAlert::severity(ContractAlert::GT_90));
    }
}
