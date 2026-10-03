<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\ValueObject\Quantity;
use App\Domain\ValueObject\Money;
use App\Domain\Exception\BusinessRuleViolation;

final class SaleItem
{
    private string $id;
    private string $saleId;
    private string $productId;
    private string $productName;
    private string $categoryName;
    private Quantity $quantity;
    private Money $unitPrice;

    public function __construct(
        string $id,
        string $saleId,
        string $productId,
        string $productName,
        string $categoryName,
        Quantity $quantity,
        Money $unitPrice
    ) {
        $trimmedProdName = trim($productName);
        if ($trimmedProdName === '') {
            throw new BusinessRuleViolation('El nombre congelado del producto no puede estar vacío.');
        }

        $trimmedCatName = trim($categoryName);
        if ($trimmedCatName === '') {
            throw new BusinessRuleViolation('El nombre congelado de la categoría no puede estar vacío.');
        }

        if (!$unitPrice->isPositive()) {
            throw new BusinessRuleViolation('El precio unitario debe ser mayor que cero.');
        }

        $this->id = $id;
        $this->saleId = $saleId;
        $this->productId = $productId;
        $this->productName = $trimmedProdName;
        $this->categoryName = $trimmedCatName;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSaleId(): string
    {
        return $this->saleId;
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

    public function getQuantity(): Quantity
    {
        return $this->quantity;
    }

    public function getUnitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function getSubtotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity->getValue());
    }
}
