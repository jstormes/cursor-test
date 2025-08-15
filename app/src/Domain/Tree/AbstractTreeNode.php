<?php

declare(strict_types=1);

namespace App\Domain\Tree;

abstract class AbstractTreeNode implements TreeNode
{
    protected ?int $id;
    protected string $name;
    protected int $treeId;
    protected ?int $parentId;
    protected int $sortOrder;
    protected array $children = [];

    public function __construct(
        ?int $id,
        string $name,
        int $treeId,
        ?int $parentId = null,
        int $sortOrder = 0
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->treeId = $treeId;
        $this->parentId = $parentId;
        $this->sortOrder = $sortOrder;
    }

    #[\Override]
    public function getId(): ?int
    {
        return $this->id;
    }

    #[\Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[\Override]
    public function getTreeId(): int
    {
        return $this->treeId;
    }

    #[\Override]
    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    #[\Override]
    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    #[\Override]
    public function addChild(TreeNode $child): void
    {
        $this->children[] = $child;
    }

    #[\Override]
    public function getChildren(): array
    {
        return $this->children;
    }

    #[\Override]
    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    #[\Override]
    abstract public function getType(): string;

    #[\Override]
    abstract public function accept(TreeNodeVisitor $visitor): string;

    #[\Override]
    abstract public function getTypeData(): array;
}
