<?php

namespace App\Helpers;

use DateTimeImmutable;

/**
 * Business days in Colombia: Monday to Friday except the national holidays
 * (Ley 51 de 1983 "Emiliani": most of them move to the following Monday;
 * the Easter ones are computed from Easter Sunday of each year).
 */
class BusinessDays
{
    /** @var array<int, string[]> */
    private static array $cache = [];

    /** @return string[] Y-m-d holidays of the year, sorted */
    public static function holidays(int $year): array
    {
        if (isset(self::$cache[$year])) {
            return self::$cache[$year];
        }

        $fixed = ['01-01', '05-01', '07-20', '08-07', '12-08', '12-25'];
        $movedToMonday = ['01-06', '03-19', '06-29', '08-15', '10-12', '11-01', '11-11'];
        $easter = self::easter($year);

        $days = array_map(fn (string $md) => "$year-$md", $fixed);
        foreach ($movedToMonday as $md) {
            $days[] = self::nextMonday(new DateTimeImmutable("$year-$md"))->format('Y-m-d');
        }
        $days[] = $easter->modify('-3 days')->format('Y-m-d');  // Jueves Santo
        $days[] = $easter->modify('-2 days')->format('Y-m-d');  // Viernes Santo
        $days[] = $easter->modify('+43 days')->format('Y-m-d'); // Ascensión (lunes)
        $days[] = $easter->modify('+64 days')->format('Y-m-d'); // Corpus Christi (lunes)
        $days[] = $easter->modify('+71 days')->format('Y-m-d'); // Sagrado Corazón (lunes)

        $days = array_values(array_unique($days));
        sort($days);

        return self::$cache[$year] = $days;
    }

    public static function isBusinessDay(string $date): bool
    {
        $d = new DateTimeImmutable($date);

        return (int) $d->format('N') <= 5 && !in_array($d->format('Y-m-d'), self::holidays((int) $d->format('Y')), true);
    }

    /** First business day strictly after $date. */
    public static function next(string $date): string
    {
        $d = new DateTimeImmutable($date);
        do {
            $d = $d->modify('+1 day');
        } while (!self::isBusinessDay($d->format('Y-m-d')));

        return $d->format('Y-m-d');
    }

    /** Business days between two dates, both included (0 when $end is before $start). */
    public static function count(string $start, string $end): int
    {
        $count = 0;
        for ($d = new DateTimeImmutable($start); $d->format('Y-m-d') <= $end; $d = $d->modify('+1 day')) {
            if (self::isBusinessDay($d->format('Y-m-d'))) {
                $count++;
            }
        }

        return $count;
    }

    /** Easter Sunday (anonymous Gregorian algorithm; no ext-calendar needed). */
    private static function easter(int $year): DateTimeImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
    }

    private static function nextMonday(DateTimeImmutable $d): DateTimeImmutable
    {
        return (int) $d->format('N') === 1 ? $d : $d->modify('next monday');
    }
}
