<?php

declare(strict_types=1);

namespace App\Application\Ports\Inbound;

final class SaleItemView
{
    private string $id;
    private string $productId;
    private string $productName;
    private string $categoryName;
    private int $quantity;
    private float $unitPrice;
    private float $subtotal;

    public function __construct(
        string $id,
        string $productId,
        string $productName,
        string $categoryName,
        int $quantity,
        float $unitPrice,
        float $subtotal
    ) {
        $this->id = $id;
        $this->productId = $productId;
        $this->productName = $productName;
        $this->categoryName = $categoryName;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->subtotal = $subtotal;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getCategoryName(): string
    {
        return $this->categoryName;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }

    public function getSubtotal(): float
    {
        return $this->subtotal;
    }
}
