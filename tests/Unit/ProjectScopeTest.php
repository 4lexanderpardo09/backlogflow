<?php

namespace Tests\Unit;

use App\Helpers\ProjectScope;
use PHPUnit\Framework\TestCase;

class ProjectScopeTest extends TestCase
{
    private const PROJECTS = [
        ['id' => 9, 'parent_id' => null, 'is_platform' => 1, 'name' => 'Mesa de Ayuda'],
        ['id' => 10, 'parent_id' => 9, 'is_platform' => 0, 'name' => 'Gastos'],
        ['id' => 17, 'parent_id' => 9, 'is_platform' => 0, 'name' => 'Confirmaciones'],
        ['id' => 5, 'parent_id' => null, 'is_platform' => 0, 'name' => 'RRHH'],
    ];

    public function testNoProjectPickedMeansNoFilter(): void
    {
        $this->assertNull(ProjectScope::ids(self::PROJECTS, 0, 0));
    }

    public function testPlatformAloneMeansItAndAllItsChildren(): void
    {
        $this->assertSame([9, 10, 17], ProjectScope::ids(self::PROJECTS, 9, 0));
    }

    public function testPlatformPlusChildMeansOnlyThatChild(): void
    {
        $this->assertSame([17], ProjectScope::ids(self::PROJECTS, 9, 17));
    }

    public function testChildOfAnotherParentIsIgnored(): void
    {
        // Child 17 belongs to 9, not to 5: the stale pick falls back to project 5.
        $this->assertSame([5], ProjectScope::ids(self::PROJECTS, 5, 17));
    }

    public function testTopLevelListsPlatformsAndStandaloneProjects(): void
    {
        $this->assertSame([9, 5], array_column(ProjectScope::topLevel(self::PROJECTS), 'id'));
    }
}
