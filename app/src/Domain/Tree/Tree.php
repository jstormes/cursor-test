<?php

declare(strict_types=1);

namespace App\Domain\Tree;

use App\Infrastructure\Time\ClockInterface;
use DateTime;
use JsonSerializable;

class Tree implements JsonSerializable
{
    private ?int $id;
    private string $name;
    private ?string $description;
    private DateTime $createdAt;
    private DateTime $updatedAt;
    private bool $isActive;
    private ClockInterface $clock;

    public function __construct(
        ?int $id,
        string $name,
        ?string $description = null,
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null,
        bool $isActive = true,
        ?ClockInterface $clock = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->clock = $clock ?? new \App\Infrastructure\Time\SystemClock();
        $this->createdAt = $createdAt ?? $this->clock->nowDateTime();
        $this->updatedAt = $updatedAt ?? $this->clock->nowDateTime();
        $this->isActive = $isActive;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = $this->clock->nowDateTime();
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
        $this->updatedAt = $this->clock->nowDateTime();
    }

    public function setActive(bool $isActive): void
    {
        $this->isActive = $isActive;
        $this->updatedAt = $this->clock->nowDateTime();
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
            'isActive' => $this->isActive,
        ];
    }
}
