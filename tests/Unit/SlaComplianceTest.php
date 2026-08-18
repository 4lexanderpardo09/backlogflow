<?php

namespace Tests\Unit;

use App\Helpers\SlaCompliance;
use App\Helpers\TrafficLight;
use PHPUnit\Framework\TestCase;

class SlaComplianceTest extends TestCase
{
    public function testAnyBreachIsRed(): void
    {
        $this->assertSame(TrafficLight::RED, SlaCompliance::semaphore(99.0, 99.0, 1));
    }

    public function testLowComplianceIsRed(): void
    {
        $this->assertSame(TrafficLight::RED, SlaCompliance::semaphore(80.0, 99.0, 0));
    }

    public function testModerateComplianceIsYellow(): void
    {
        $this->assertSame(TrafficLight::YELLOW, SlaCompliance::semaphore(90.0, 92.0, 0));
    }

    public function testHighComplianceNoBreachIsGreen(): void
    {
        $this->assertSame(TrafficLight::GREEN, SlaCompliance::semaphore(98.0, 97.0, 0));
    }
}
