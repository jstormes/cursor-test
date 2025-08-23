<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Domain\Tree\Tree;
use App\Infrastructure\Time\ClockInterface;
use DateTime;

class TreeDataMapper implements DataMapper
{
    public function __construct(private ClockInterface $clock)
    {
    }
    #[\Override]
    public function mapToEntity(array $data): Tree
    {
        return new Tree(
            (int) $data['id'],
            $data['name'],
            $data['description'] ?? null,
            new DateTime($data['created_at']),
            new DateTime($data['updated_at']),
            (bool) $data['is_active'],
            $this->clock
        );
    }

    #[\Override]
    public function mapToArray(object $entity): array
    {
        if (!$entity instanceof Tree) {
            throw new \InvalidArgumentException('Entity must be an instance of Tree');
        }

        return [
            'id' => $entity->getId(),
            'name' => $entity->getName(),
            'description' => $entity->getDescription(),
            'created_at' => $entity->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $entity->getUpdatedAt()->format('Y-m-d H:i:s'),
            'is_active' => $entity->isActive() ? 1 : 0,
        ];
    }

    #[\Override]
    public function mapToEntities(array $data): array
    {
        return array_map([$this, 'mapToEntity'], $data);
    }
}
