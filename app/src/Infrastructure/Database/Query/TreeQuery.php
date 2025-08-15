<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Query;

class TreeQuery
{
    private array $filters = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private int $offset = 0;

    public function withActive(bool $isActive): self
    {
        $this->filters['is_active'] = $isActive ? 1 : 0;
        return $this;
    }

    public function withNameLike(string $name): self
    {
        $this->filters['name_like'] = $name;
        return $this;
    }

    public function withDescriptionLike(string $description): self
    {
        $this->filters['description_like'] = $description;
        return $this;
    }

    public function orderByCreatedAt(string $direction = 'ASC'): self
    {
        $this->orderBy['created_at'] = $direction;
        return $this;
    }

    public function orderByName(string $direction = 'ASC'): self
    {
        $this->orderBy['name'] = $direction;
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function buildSql(): string
    {
        $sql = 'SELECT id, name, description, created_at, updated_at, is_active FROM trees';
        $conditions = [];

        foreach ($this->filters as $key => $value) {
            switch ($key) {
                case 'is_active':
                    $conditions[] = 'is_active = ?';
                    break;
                case 'name_like':
                    $conditions[] = 'name LIKE ?';
                    break;
                case 'description_like':
                    $conditions[] = 'description LIKE ?';
                    break;
            }
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        if (!empty($this->orderBy)) {
            $orderClauses = [];
            foreach ($this->orderBy as $column => $direction) {
                $orderClauses[] = "{$column} {$direction}";
            }
            $sql .= ' ORDER BY ' . implode(', ', $orderClauses);
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ?';
        }

        if ($this->offset > 0) {
            $sql .= ' OFFSET ?';
        }

        return $sql;
    }
}
