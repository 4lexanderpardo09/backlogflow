<?php

namespace Tests\Unit;

use App\Helpers\SprintClose;
use PHPUnit\Framework\TestCase;

class SprintCloseTest extends TestCase
{
    public function testOnlyCompletedIsFinished(): void
    {
        $this->assertTrue(SprintClose::isFinished('completed'));
        $this->assertFalse(SprintClose::isFinished('in_review'));
        $this->assertFalse(SprintClose::isFinished('in_testing'));
        $this->assertFalse(SprintClose::isFinished('in_progress'));
    }

    public function testClosingWithPendingActivitiesNeedsADecisionForEach(): void
    {
        $errors = SprintClose::validate([10, 11], [10 => ['action' => 'return']], true);

        $this->assertSame(['Falta decidir qué pasa con 1 actividad(es) sin terminar.'], $errors);
    }

    public function testCancellingRequiresAReason(): void
    {
        $this->assertSame(
            ['Escribe el motivo de cancelación de 1 actividad(es).'],
            SprintClose::validate([10], [10 => ['action' => 'cancel', 'reason' => '   ']], false)
        );
        $this->assertSame([], SprintClose::validate([10], [10 => ['action' => 'cancel', 'reason' => 'Ya no aplica']], false));
    }

    public function testMovingNeedsANextSprint(): void
    {
        $decisions = [10 => ['action' => 'move'], 11 => ['action' => 'return']];

        $this->assertTrue(SprintClose::needsTarget($decisions));
        $this->assertSame(
            ['Elige o crea el siguiente sprint para las actividades que se mueven.'],
            SprintClose::validate([10, 11], $decisions, false)
        );
        $this->assertSame([], SprintClose::validate([10, 11], $decisions, true));
    }

    public function testNothingPendingIsValidWithoutTarget(): void
    {
        $this->assertSame([], SprintClose::validate([], [], false));
        $this->assertFalse(SprintClose::needsTarget([]));
    }

    public function testClosedSprintIsNotEditable(): void
    {
        $this->assertTrue(SprintClose::isEditable(['status' => 'open']));
        $this->assertFalse(SprintClose::isEditable(['status' => 'closed']));
    }

    public function testOverdueOpenSprint(): void
    {
        $sprint = ['status' => 'open', 'end_date' => '2026-10-19'];

        $this->assertTrue(SprintClose::isOverdueOpen($sprint, '2026-10-27'));
        $this->assertFalse(SprintClose::isOverdueOpen($sprint, '2026-10-19'));
        $this->assertFalse(SprintClose::isOverdueOpen(['status' => 'closed'] + $sprint, '2026-10-27'));
    }

    public function testDueAfterSprintEnd(): void
    {
        $this->assertTrue(SprintClose::dueAfterEnd('2026-10-25', '2026-10-19', 'in_review'));
        $this->assertFalse(SprintClose::dueAfterEnd('2026-10-19', '2026-10-19', 'in_progress'));
        $this->assertFalse(SprintClose::dueAfterEnd('2026-10-25', '2026-10-19', 'completed'));
        $this->assertFalse(SprintClose::dueAfterEnd(null, '2026-10-19', 'pending'));
    }

    public function testNextSprintStartsNextBusinessDayAndLastsTheSameCalendarDays(): void
    {
        // Sprint 5–19 Oct (14 days) closed on Tuesday 27 Oct.
        $this->assertSame(
            ['start_date' => '2026-10-28', 'end_date' => '2026-11-11'],
            SprintClose::suggestedDates('2026-10-27', '2026-10-05', '2026-10-19')
        );
        // Closed on Friday 30 Oct: Monday 2 Nov is a holiday, so it starts on Tuesday.
        $this->assertSame(
            ['start_date' => '2026-11-03', 'end_date' => '2026-11-17'],
            SprintClose::suggestedDates('2026-10-30', '2026-10-05', '2026-10-19')
        );
    }

    public function testSuggestedName(): void
    {
        $this->assertSame('Sprint 5', SprintClose::suggestedName('Sprint 4'));
        $this->assertSame('Sprint comercial 2', SprintClose::suggestedName('Sprint comercial'));
        $this->assertSame('', SprintClose::suggestedName(null));
    }

    public function testWeeksForPeriod(): void
    {
        $this->assertSame(2, SprintClose::weeksFor('2026-10-05', '2026-10-19'));
        $this->assertSame(3, SprintClose::weeksFor('2026-10-05', '2026-10-20'));
        $this->assertSame(1, SprintClose::weeksFor('2026-10-05', '2026-10-05'));
    }
}
