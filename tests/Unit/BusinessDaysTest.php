<?php

namespace Tests\Unit;

use App\Helpers\BusinessDays;
use PHPUnit\Framework\TestCase;

class BusinessDaysTest extends TestCase
{
    public function testColombianHolidays2026(): void
    {
        $this->assertSame([
            '2026-01-01', '2026-01-12', '2026-03-23', '2026-04-02', '2026-04-03', '2026-05-01',
            '2026-05-18', '2026-06-08', '2026-06-15', '2026-06-29', '2026-07-20', '2026-08-07',
            '2026-08-17', '2026-10-12', '2026-11-02', '2026-11-16', '2026-12-08', '2026-12-25',
        ], BusinessDays::holidays(2026));
    }

    public function testNextSkipsTheWeekend(): void
    {
        $this->assertSame('2026-10-28', BusinessDays::next('2026-10-27'));
        $this->assertSame('2026-10-26', BusinessDays::next('2026-10-23'));
    }

    public function testNextSkipsAMondayHoliday(): void
    {
        // Friday 9 Oct → Monday 12 Oct is Día de la Raza → Tuesday 13.
        $this->assertSame('2026-10-13', BusinessDays::next('2026-10-09'));
        // Friday 30 Oct → Monday 2 Nov is Todos los Santos → Tuesday 3.
        $this->assertSame('2026-11-03', BusinessDays::next('2026-10-30'));
    }

    public function testCountLeavesOutWeekendsAndHolidays(): void
    {
        // 5–19 Oct 2026: 11 weekdays, minus Monday 12 Oct.
        $this->assertSame(10, BusinessDays::count('2026-10-05', '2026-10-19'));
        $this->assertSame(0, BusinessDays::count('2026-10-19', '2026-10-05'));
    }
}
