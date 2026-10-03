<?php

declare(strict_types=1);

namespace App\Application\Ports\Inbound;

final class PagedResult
{
    /** @var array<mixed> */
    private array $items;
    private int $totalItems;
    private int $page;
    private int $perPage;
    private int $totalPages;

    /**
     * @param array<mixed> $items
     */
    public function __construct(array $items, int $totalItems, int $page, int $perPage)
    {
        $this->items = $items;
        $this->totalItems = $totalItems;
        $this->page = $page;
        $this->perPage = $perPage;
        $this->totalPages = $perPage > 0 ? (int) ceil($totalItems / $perPage) : 0;
    }

    /**
     * @return array<mixed>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }
}
