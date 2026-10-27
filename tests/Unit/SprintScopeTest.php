<?php

namespace Tests\Unit;

use App\Helpers\SprintScope;
use PHPUnit\Framework\TestCase;

class SprintScopeTest extends TestCase
{
    private function row(string $origin, string $status, ?float $hours, ?float $committed = null, ?string $outcome = null, ?string $removed = null): array
    {
        return ['origin' => $origin, 'status_code' => $status, 'estimated_hours' => $hours,
            'committed_hours' => $committed, 'outcome' => $outcome, 'removed_at' => $removed];
    }

    public function testWhatIsAddedAfterTheStartIsAlcanceAgregado(): void
    {
        $this->assertSame('committed', SprintScope::originFor('2026-10-28', '2026-10-27'));
        $this->assertSame('committed', SprintScope::originFor('2026-10-28', '2026-10-28'));
        $this->assertSame('added', SprintScope::originFor('2026-10-28', '2026-10-29'));
    }

    public function testClosingWithPendingActivities(): void
    {
        $s = SprintScope::summary([
            $this->row('committed', 'completed', 4, 4, 'completed'),
            $this->row('committed', 'in_review', 4, 4, 'moved'),
            $this->row('committed', 'in_progress', 2, 2, 'returned'),
            $this->row('added', 'completed', 3, null, 'completed'),
        ]);

        $this->assertSame(3, $s['committed_count']);
        $this->assertSame(10.0, $s['committed_hours']);
        $this->assertSame(4.0, $s['committed_completed_hours']);
        // Only the committed ones: 4 / 10. The alcance agregado is reported apart.
        $this->assertSame(40.0, $s['compliance_percent']);
        $this->assertSame(1, $s['added_count']);
        $this->assertSame(1, $s['added_completed_count']);
        $this->assertSame(2, $s['completed_count']);
        $this->assertSame(1, $s['moved_count']);
        $this->assertSame(1, $s['returned_count']);
    }

    public function testCancelledWithReasonStillCountsAsCommitted(): void
    {
        $s = SprintScope::summary([
            $this->row('committed', 'completed', 2, 2, 'completed'),
            $this->row('committed', 'cancelled', 2, 2, 'cancelled'),
        ]);

        $this->assertSame(1, $s['cancelled_count']);
        $this->assertSame(50.0, $s['compliance_percent']);
    }

    public function testCommittedHoursAreTheOnesAtTheStart(): void
    {
        // Estimated 4 h at the start, re-estimated to 8 h later: cumplimiento never goes above 100 %.
        $s = SprintScope::summary([$this->row('committed', 'completed', 8, 4)]);

        $this->assertSame(4.0, $s['committed_hours']);
        $this->assertSame(100.0, $s['compliance_percent']);
        $this->assertSame(8.0, $s['completed_hours']);
    }

    public function testMovedActivityFinishedInTheNextSprintDoesNotCountHere(): void
    {
        $s = SprintScope::summary([
            $this->row('committed', 'completed', 2, 2, 'moved'),
            $this->row('committed', 'completed', 2, 2, 'completed'),
        ]);

        $this->assertSame(1, $s['completed_count']);
        $this->assertSame(50.0, $s['compliance_percent']);
    }

    public function testRemovedAfterTheStartStaysCommitted(): void
    {
        $s = SprintScope::summary([
            $this->row('committed', 'pending', 2, 2, null, '2026-10-10 09:00:00'),
            $this->row('committed', 'completed', 2, 2),
        ]);

        $this->assertSame(1, $s['removed_count']);
        $this->assertSame(1, $s['active_count']);
        $this->assertSame(50.0, $s['compliance_percent']);
    }

    public function testLegacyActivitiesCountAsCommittedWithCurrentHours(): void
    {
        // Not estimated (null) counts as 1 h.
        $s = SprintScope::summary([
            $this->row('legacy', 'completed', null),
            $this->row('legacy', 'in_testing', 3),
            $this->row('carried', 'completed', 2, 2),
        ]);

        $this->assertTrue($s['committed_from_legacy']);
        $this->assertSame(6.0, $s['committed_hours']);
        $this->assertSame(50.0, $s['compliance_percent']);
        $this->assertSame(1, $s['carried_count']);
    }

    public function testEmptySprint(): void
    {
        $s = SprintScope::summary([]);

        $this->assertSame(0, $s['committed_count']);
        $this->assertSame(0.0, $s['compliance_percent']);
    }
}
