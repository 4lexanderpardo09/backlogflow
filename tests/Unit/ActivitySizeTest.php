<?php

namespace Tests\Unit;

use App\Helpers\ActivitySize;
use App\Helpers\Progress;
use PHPUnit\Framework\TestCase;

class ActivitySizeTest extends TestCase
{
    public function testSizesMapToHours(): void
    {
        $this->assertSame(['XS' => 1.0, 'S' => 2.0, 'M' => 4.0, 'L' => 8.0, 'XL' => 16.0], ActivitySize::HOURS);
    }

    public function testFromHoursFindsTheExactSize(): void
    {
        $this->assertSame('M', ActivitySize::fromHours('4.00'));
        $this->assertSame('XL', ActivitySize::fromHours(16));
        $this->assertNull(ActivitySize::fromHours(3));     // custom hours
        $this->assertNull(ActivitySize::fromHours(null));  // not estimated
    }

    public function testXlIsTooBig(): void
    {
        $this->assertTrue(ActivitySize::isTooBig(16));
        $this->assertTrue(ActivitySize::isTooBig('20'));
        $this->assertFalse(ActivitySize::isTooBig(8));
        $this->assertFalse(ActivitySize::isTooBig(null));
    }

    public function testCountsActivitiesWithoutEstimateIgnoringCancelled(): void
    {
        $this->assertSame(2, ActivitySize::countUnestimated([
            ['estimated_hours' => null, 'status_code' => 'pending'],
            ['estimated_hours' => '', 'status_code' => 'in_progress'],
            ['estimated_hours' => '4.00', 'status_code' => 'pending'],
            ['estimated_hours' => null, 'status_code' => 'cancelled'],
        ]));
    }

    public function testUnestimatedActivityStillWeighsOneHour(): void
    {
        // Not estimated (NULL) counts as 1 h: (100*1 + 0*3) / 4 = 25 %.
        $this->assertEqualsWithDelta(25.0, Progress::byHours([
            ['progress_percent' => 100, 'estimated_hours' => null],
            ['progress_percent' => 0, 'estimated_hours' => 3],
        ]), 0.001);
    }
}
