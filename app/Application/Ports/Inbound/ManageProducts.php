<?php

declare(strict_types=1);

namespace App\Application\Ports\Inbound;

use App\Application\Model\PageRequest;

interface ManageProducts
{
    public function listProducts(?string $categoryId, PageRequest $pageRequest): PagedResult;

    public function getProduct(string $id): ProductView;

    public function createProduct(string $name, float $price, int $stock, string $categoryId): ProductView;

    public function updateProduct(string $id, string $name, float $price, int $stock, string $categoryId): ProductView;

    public function deleteProduct(string $id): void;

    public function uploadImage(string $id, string $imageBinary, string $extension): ProductView;

    public function getImage(string $key): ?string;

    /**
     * @return array<array{id: string, name: string}>
     */
    public function listCategories(): array;
}
