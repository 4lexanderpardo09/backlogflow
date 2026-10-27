<?php

namespace Tests\Unit;

use App\Helpers\ActivitySize;
use PHPUnit\Framework\TestCase;

class ActivitySizeTest extends TestCase
{
    public function testSizesFillTheAgreedHours(): void
    {
        $this->assertSame(['XS' => 1.0, 'S' => 2.0, 'M' => 4.0, 'L' => 8.0, 'XL' => 16.0], ActivitySize::SIZES);
    }

    public function testSizeIsRecognisedFromTheStoredHours(): void
    {
        $this->assertSame('M', ActivitySize::sizeFor('4.00'));
        $this->assertSame('XL', ActivitySize::sizeFor(16));
        $this->assertSame('XS', ActivitySize::sizeFor(1.0));
    }

    public function testCustomOrMissingHoursHaveNoSize(): void
    {
        $this->assertNull(ActivitySize::sizeFor('3.50'));
        $this->assertNull(ActivitySize::sizeFor(null));
        $this->assertNull(ActivitySize::sizeFor(''));
    }

    public function testCountsActivitiesWithoutHoursIgnoringCancelled(): void
    {
        $this->assertSame(2, ActivitySize::countUnestimated([
            ['estimated_hours' => null, 'status_code' => 'pending'],
            ['estimated_hours' => '8.00', 'status_code' => 'in_progress'],
            ['status_code' => 'completed'],
            ['estimated_hours' => null, 'status_code' => 'cancelled'],
        ]));
    }
}
