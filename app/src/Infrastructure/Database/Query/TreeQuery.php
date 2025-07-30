<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Query;

class TreeQuery
{
    private array $filters = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private int $offset = 0;

    public function withActive(bool $active = true): self
    {
        $this->filters['is_active'] = $active ? 1 : 0;
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

    public function orderByCreatedAt(string $direction = 'DESC'): self
    {
        $this->orderBy['created_at'] = strtoupper($direction);
        return $this;
    }

    public function orderByName(string $direction = 'ASC'): self
    {
        $this->orderBy['name'] = strtoupper($direction);
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
        $params = [];
        
        foreach ($this->filters as $key => $value) {
            switch ($key) {
                case 'is_active':
                    $conditions[] = 'is_active = ?';
                    $params[] = $value;
                    break;
                case 'name_like':
                    $conditions[] = 'name LIKE ?';
                    $params[] = "%{$value}%";
                    break;
                case 'description_like':
                    $conditions[] = 'description LIKE ?';
                    $params[] = "%{$value}%";
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
            $params[] = $this->limit;
        }
        
        if ($this->offset > 0) {
            $sql .= ' OFFSET ?';
            $params[] = $this->offset;
        }
        
        return $sql;
    }
} 