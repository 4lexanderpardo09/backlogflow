<?php

namespace App\Helpers;

/**
 * Buckets a contract/license/certificate expiration date into the alert
 * ranges required by spec section 16: >90, 60-90, 30-60, <30, expired.
 */
class ContractAlert
{
    public const EXPIRED = 'expired';
    public const LT_30 = 'lt_30';
    public const D30_60 = 'd30_60';
    public const D60_90 = 'd60_90';
    public const GT_90 = 'gt_90';

    public static function bucket(string $expirationDate, ?string $today = null): string
    {
        $today ??= date('Y-m-d');
        $daysRemaining = DateMath::daysBetween($today, $expirationDate);

        return match (true) {
            $daysRemaining < 0 => self::EXPIRED,
            $daysRemaining < 30 => self::LT_30,
            $daysRemaining < 60 => self::D30_60,
            $daysRemaining < 90 => self::D60_90,
            default => self::GT_90,
        };
    }

    public static function severity(string $bucket): string
    {
        return match ($bucket) {
            self::EXPIRED, self::LT_30 => TrafficLight::RED,
            self::D30_60 => TrafficLight::YELLOW,
            default => TrafficLight::GREEN,
        };
    }
}
