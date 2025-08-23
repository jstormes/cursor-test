<?php

declare(strict_types=1);

namespace App\Tests\Utilities;

use App\Domain\Tree\Tree;
use App\Infrastructure\Time\ClockInterface;
use App\Tests\Utilities\MockClock;
use DateTime;

class TreeFactory
{
    public static function create(
        ?int $id = null,
        string $name = 'Test Tree',
        ?string $description = 'Test Description',
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null,
        bool $isActive = true,
        ?ClockInterface $clock = null
    ): Tree {
        $clock = $clock ?? new MockClock();
        $createdAt = $createdAt ?? new DateTime('2023-01-01 10:00:00');
        $updatedAt = $updatedAt ?? new DateTime('2023-01-01 10:00:00');
        
        return new Tree($id, $name, $description, $createdAt, $updatedAt, $isActive, $clock);
    }

    public static function createActive(?int $id = 1, string $name = 'Active Tree'): Tree
    {
        return self::create($id, $name, 'Active tree description', null, null, true);
    }

    public static function createInactive(?int $id = 2, string $name = 'Inactive Tree'): Tree
    {
        return self::create($id, $name, 'Inactive tree description', null, null, false);
    }

    public static function createWithoutDescription(?int $id = 3, string $name = 'Tree Without Description'): Tree
    {
        return self::create($id, $name, null);
    }

    public static function createMultiple(int $count = 3): array
    {
        $trees = [];
        for ($i = 1; $i <= $count; $i++) {
            $trees[] = self::create($i, "Tree $i", "Description for tree $i");
        }
        return $trees;
    }
}