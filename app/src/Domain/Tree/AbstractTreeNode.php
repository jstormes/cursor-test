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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTreeId(): int
    {
        return $this->treeId;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function addChild(TreeNode $child): void
    {
        $this->children[] = $child;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    abstract public function getType(): string;
    
    abstract public function accept(TreeNodeVisitor $visitor): string;

    abstract public function getTypeData(): array;
} 