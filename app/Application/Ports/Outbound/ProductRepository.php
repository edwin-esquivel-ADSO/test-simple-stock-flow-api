<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

use App\Domain\Model\Product;
use App\Application\Ports\Inbound\PagedResult;
use App\Application\Model\PageRequest;

interface ProductRepository
{
    public function findById(string $id): ?Product;

    /**
     * @param string[] $ids
     * @return array<string, Product>
     */
    public function findByIds(array $ids): array;

    public function save(Product $product): void;

    public function search(?string $categoryId, PageRequest $pageRequest): PagedResult;

    public function hasActiveSales(string $productId): bool;
}
