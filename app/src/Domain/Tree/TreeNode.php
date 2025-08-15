<?php

declare(strict_types=1);

namespace App\Domain\Tree;

interface TreeNode
{
    public function getId(): ?int;
    public function getName(): string;
    public function getTreeId(): int;
    public function getParentId(): ?int;
    public function getSortOrder(): int;
    public function addChild(TreeNode $child): void;
    public function getChildren(): array;
    public function hasChildren(): bool;
    public function getType(): string;
    public function getTypeData(): array;
    public function accept(TreeNodeVisitor $visitor): string;
} 