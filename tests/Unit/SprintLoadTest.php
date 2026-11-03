<?php

namespace Tests\Unit;

use App\Helpers\SprintLoad;
use PHPUnit\Framework\TestCase;

class SprintLoadTest extends TestCase
{
    private function act(int $dev, string $name, string $project, string $status, ?float $hours): array
    {
        return ['developer_id' => $dev, 'developer_name' => $name, 'project_name' => $project,
            'status_code' => $status, 'estimated_hours' => $hours];
    }

    public function testGroupsHoursPerDeveloperAgainstCapacity(): void
    {
        $rows = SprintLoad::byDeveloper([
            $this->act(3, 'Julian', 'Activos', 'pending', 60),
            $this->act(3, 'Julian', 'Activos', 'completed', 30),
            $this->act(1, 'Alexander', 'RRHH', 'pending', 2),
            $this->act(1, 'Alexander', 'Gastos', 'pending', null),
        ], 10);

        $this->assertSame('Julian', $rows[0]['developer_name']);
        $this->assertSame(2, $rows[0]['activity_count']);
        $this->assertSame(90.0, $rows[0]['hours']);
        $this->assertSame(30.0, $rows[0]['done_hours']);
        $this->assertSame(60.0, $rows[0]['remaining_hours']);
        $this->assertSame(80.0, $rows[0]['capacity_hours']);
        $this->assertSame(112.5, $rows[0]['load_percent']);
        $this->assertTrue($rows[0]['overloaded']);

        // Not estimated counts as 1 h.
        $this->assertSame(3.0, $rows[1]['hours']);
        $this->assertSame(['Gastos', 'RRHH'], $rows[1]['projects']);
        $this->assertFalse($rows[1]['overloaded']);
    }

    public function testCancelledActivitiesDoNotCount(): void
    {
        $rows = SprintLoad::byDeveloper([$this->act(1, 'A', 'P', 'cancelled', 8)], 10);

        $this->assertSame([], $rows);
    }

    public function testNoBusinessDaysMeansNoCapacity(): void
    {
        $rows = SprintLoad::byDeveloper([$this->act(1, 'A', 'P', 'pending', 8)], 0);

        $this->assertSame(0.0, $rows[0]['load_percent']);
        $this->assertFalse($rows[0]['overloaded']);
    }
}
