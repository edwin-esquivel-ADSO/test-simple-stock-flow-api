<?php

declare(strict_types=1);

namespace App\Application\Ports\Inbound;

use DateTimeImmutable;

final class SaleView
{
    private string $id;
    private DateTimeImmutable $soldAt;
    private string $soldByUsername;
    private float $total;
    /** @var SaleItemView[] */
    private array $items;

    /**
     * @param SaleItemView[] $items
     */
    public function __construct(
        string $id,
        DateTimeImmutable $soldAt,
        string $soldByUsername,
        float $total,
        array $items
    ) {
        $this->id = $id;
        $this->soldAt = $soldAt;
        $this->soldByUsername = $soldByUsername;
        $this->total = $total;
        $this->items = $items;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSoldAt(): DateTimeImmutable
    {
        return $this->soldAt;
    }

    public function getSoldByUsername(): string
    {
        return $this->soldByUsername;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    /**
     * @return SaleItemView[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
