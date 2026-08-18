<?php

namespace Tests\Unit;

use App\Helpers\TrafficLight;
use PHPUnit\Framework\TestCase;

class TrafficLightTest extends TestCase
{
    private const TODAY = '2026-08-14';

    public function testCompletedProjectIsAlwaysGreen(): void
    {
        $this->assertSame(TrafficLight::GREEN, TrafficLight::forProject(40.0, '2026-01-01', 5, 5, true, self::TODAY));
    }

    public function testPastDeadlineIsRed(): void
    {
        $this->assertSame(TrafficLight::RED, TrafficLight::forProject(80.0, '2026-08-01', 0, 0, false, self::TODAY));
    }

    public function testTwoOrMoreOverdueActivitiesIsRed(): void
    {
        $this->assertSame(TrafficLight::RED, TrafficLight::forProject(80.0, '2026-12-01', 2, 0, false, self::TODAY));
    }

    public function testOneOverdueActivityIsYellow(): void
    {
        $this->assertSame(TrafficLight::YELLOW, TrafficLight::forProject(80.0, '2026-12-01', 1, 0, false, self::TODAY));
    }

    public function testOpenCriticalActivityIsYellow(): void
    {
        $this->assertSame(TrafficLight::YELLOW, TrafficLight::forProject(80.0, '2026-12-01', 0, 1, false, self::TODAY));
    }

    public function testCloseToDeadlineWithLowProgressIsYellow(): void
    {
        $this->assertSame(TrafficLight::YELLOW, TrafficLight::forProject(50.0, '2026-08-20', 0, 0, false, self::TODAY));
    }

    public function testHealthyProjectIsGreen(): void
    {
        $this->assertSame(TrafficLight::GREEN, TrafficLight::forProject(50.0, '2026-12-01', 0, 0, false, self::TODAY));
    }
}
