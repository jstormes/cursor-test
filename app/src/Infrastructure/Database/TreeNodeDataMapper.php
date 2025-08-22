<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Domain\Tree\AbstractTreeNode;
use App\Domain\Tree\ButtonNode;
use App\Domain\Tree\SimpleNode;
use DateTime;

class TreeNodeDataMapper implements DataMapper
{
    #[\Override]
    public function mapToEntity(array $data): AbstractTreeNode
    {
        $node = match ($data['type_class']) {
            'SimpleNode' => new SimpleNode(
                (int) $data['id'],
                $data['name'],
                (int) $data['tree_id'],
                (int) $data['parent_id'] ?: null,
                (int) $data['sort_order']
            ),
            'ButtonNode' => new ButtonNode(
                (int) $data['id'],
                $data['name'],
                (int) $data['tree_id'],
                (int) $data['parent_id'] ?: null,
                (int) $data['sort_order'],
                json_decode($data['type_data'] ?? '{}', true)
            ),
            default => throw new \InvalidArgumentException("Unknown node type: {$data['type_class']}")
        };

        return $node;
    }

    #[\Override]
    public function mapToArray(object $entity): array
    {
        if (!$entity instanceof AbstractTreeNode) {
            throw new \InvalidArgumentException('Entity must be an instance of AbstractTreeNode');
        }

        $data = [
            'id' => $entity->getId(),
            'tree_id' => $entity->getTreeId(),
            'parent_id' => $entity->getParentId(),
            'name' => $entity->getName(),
            'sort_order' => $entity->getSortOrder(),
            'type_class' => $entity->getType(),
            'type_data' => json_encode($entity->getTypeData()),
        ];

        return $data;
    }

    #[\Override]
    public function mapToEntities(array $data): array
    {
        return array_map([$this, 'mapToEntity'], $data);
    }
}
