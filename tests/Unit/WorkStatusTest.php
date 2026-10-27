<?php

namespace Tests\Unit;

use App\Helpers\WorkStatus;
use PHPUnit\Framework\TestCase;

class WorkStatusTest extends TestCase
{
    private static function act(string $status, int $progress): array
    {
        return ['status_code' => $status, 'progress_percent' => $progress];
    }

    public function testNoActivitiesIsNotStarted(): void
    {
        $this->assertSame(WorkStatus::NOT_STARTED, WorkStatus::derive([]));
    }

    public function testNothingWithProgressIsNotStarted(): void
    {
        $this->assertSame(WorkStatus::NOT_STARTED, WorkStatus::derive([self::act('pending', 0), self::act('blocked', 0)]));
    }

    public function testAllCompletedOrCancelledIsCompleted(): void
    {
        $this->assertSame(WorkStatus::COMPLETED, WorkStatus::derive([self::act('completed', 100), self::act('cancelled', 0)]));
    }

    public function testAnythingElseIsInProgress(): void
    {
        $this->assertSame(WorkStatus::IN_PROGRESS, WorkStatus::derive([self::act('completed', 100), self::act('pending', 0)]));
        $this->assertSame(WorkStatus::IN_PROGRESS, WorkStatus::derive([self::act('in_progress', 30)]));
    }

    public function testCancelledProgressDoesNotStartIt(): void
    {
        $this->assertSame(WorkStatus::NOT_STARTED, WorkStatus::derive([self::act('cancelled', 40), self::act('pending', 0)]));
    }

    public function testPendingBacklogAtHundredPercentBecomesCompleted(): void
    {
        // Reported: a backlog left in "Pendiente" while all its activities are finished.
        $derived = WorkStatus::derive([self::act('completed', 100), self::act('completed', 100)]);

        $this->assertSame('completed', WorkStatus::forBacklog('pending', $derived));
    }

    public function testNotStartedProjectWithProgressBecomesInProgress(): void
    {
        // Reported: a project in "Sin iniciar" showing 94.3 %.
        $derived = WorkStatus::derive([self::act('completed', 100), self::act('pending', 0)]);

        $this->assertSame('in_progress', WorkStatus::forProject('not_started', $derived));
    }

    public function testBacklogCodesMapToTheCatalog(): void
    {
        $this->assertSame('pending', WorkStatus::forBacklog('in_development', WorkStatus::NOT_STARTED));
        $this->assertSame('in_development', WorkStatus::forBacklog('pending', WorkStatus::IN_PROGRESS));
    }

    public function testSpecialManualStatusesAreKept(): void
    {
        foreach (WorkStatus::BACKLOG_MANUAL as $code) {
            $this->assertSame($code, WorkStatus::forBacklog($code, WorkStatus::COMPLETED));
        }
        foreach (WorkStatus::PROJECT_MANUAL as $code) {
            $this->assertSame($code, WorkStatus::forProject($code, WorkStatus::IN_PROGRESS));
        }
    }

    public function testDelayedProjectIsRecomputed(): void
    {
        // "Atrasado" is now shown as overdue activities + days late, not as a status.
        $this->assertSame('in_progress', WorkStatus::forProject('delayed', WorkStatus::IN_PROGRESS));
    }
}
