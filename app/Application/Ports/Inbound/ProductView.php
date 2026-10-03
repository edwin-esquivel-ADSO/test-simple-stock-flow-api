<?php

declare(strict_types=1);

namespace App\Application\Ports\Inbound;

final class ProductView
{
    private string $id;
    private string $name;
    private float $price;
    private int $stock;
    private string $categoryId;
    private string $categoryName;
    private ?string $imageUrl;
    private bool $isActive;

    public function __construct(
        string $id,
        string $name,
        float $price,
        int $stock,
        string $categoryId,
        string $categoryName,
        ?string $imageUrl,
        bool $isActive
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->stock = $stock;
        $this->categoryId = $categoryId;
        $this->categoryName = $categoryName;
        $this->imageUrl = $imageUrl;
        $this->isActive = $isActive;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function getCategoryId(): string
    {
        return $this->categoryId;
    }

    public function getCategoryName(): string
    {
        return $this->categoryName;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
