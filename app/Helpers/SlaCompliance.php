<?php

namespace App\Helpers;

/**
 * Monthly SLA compliance semaphore (spec section 14): green = meets SLA,
 * yellow = at risk of breach, red = breached.
 */
class SlaCompliance
{
    public static function semaphore(float $responseCompliancePct, float $resolutionCompliancePct, int $breachCount): string
    {
        if ($breachCount > 0 || $responseCompliancePct < 85 || $resolutionCompliancePct < 85) {
            return TrafficLight::RED;
        }

        if ($responseCompliancePct < 95 || $resolutionCompliancePct < 95) {
            return TrafficLight::YELLOW;
        }

        return TrafficLight::GREEN;
    }
}
