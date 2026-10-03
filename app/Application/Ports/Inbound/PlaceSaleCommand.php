<?php

declare(strict_types=1);

namespace App\Application\Ports\Inbound;

final class PlaceSaleCommand
{
    private string $soldByUserId;
    private string $soldByUsername;
    /** @var PlaceSaleItemCommand[] */
    private array $items;

    /**
     * @param PlaceSaleItemCommand[] $items
     */
    public function __construct(string $soldByUserId, string $soldByUsername, array $items)
    {
        $this->soldByUserId = $soldByUserId;
        $this->soldByUsername = $soldByUsername;
        $this->items = $items;
    }

    public function getSoldByUserId(): string
    {
        return $this->soldByUserId;
    }

    public function getSoldByUsername(): string
    {
        return $this->soldByUsername;
    }

    /**
     * @return PlaceSaleItemCommand[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
