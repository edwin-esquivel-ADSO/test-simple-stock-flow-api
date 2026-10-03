<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\ValueObject\Money;
use App\Domain\Exception\BusinessRuleViolation;
use App\Domain\Exception\InsufficientStockException;
use App\Domain\Exception\InactiveProductException;
use DateTimeImmutable;

final class Product
{
    private string $id;
    private string $name;
    private Money $price;
    private int $stock;
    private string $categoryId;
    private ?string $imageKey;
    private ?DateTimeImmutable $deletedAt;

    public function __construct(
        string $id,
        string $name,
        Money $price,
        int $stock,
        string $categoryId,
        ?string $imageKey = null,
        ?DateTimeImmutable $deletedAt = null
    ) {
        $trimmedName = trim($name);
        if ($trimmedName === '') {
            throw new BusinessRuleViolation('El nombre del producto no puede estar vacío.');
        }

        if (!$price->isPositive()) {
            throw new BusinessRuleViolation('El precio debe ser mayor que cero.');
        }

        if ($stock < 0) {
            throw new BusinessRuleViolation('El stock no puede ser negativo.');
        }

        if (trim($categoryId) === '') {
            throw new BusinessRuleViolation('La categoría es obligatoria.');
        }

        $this->id = $id;
        $this->name = $trimmedName;
        $this->price = $price;
        $this->stock = $stock;
        $this->categoryId = $categoryId;
        $this->imageKey = $imageKey;
        $this->deletedAt = $deletedAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): Money
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

    public function getImageKey(): ?string
    {
        return $this->imageKey;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isActive(): bool
    {
        return $this->deletedAt === null;
    }

    public function rename(string $newName): void
    {
        $trimmed = trim($newName);
        if ($trimmed === '') {
            throw new BusinessRuleViolation('El nombre del producto no puede estar vacío.');
        }
        $this->name = $trimmed;
    }

    public function changePrice(Money $newPrice): void
    {
        if (!$newPrice->isPositive()) {
            throw new BusinessRuleViolation('El precio debe ser mayor que cero.');
        }
        $this->price = $newPrice;
    }

    public function updateCategory(string $newCategoryId): void
    {
        if (trim($newCategoryId) === '') {
            throw new BusinessRuleViolation('La categoría es obligatoria.');
        }
        $this->categoryId = $newCategoryId;
    }

    public function setImageKey(?string $imageKey): void
    {
        $this->imageKey = $imageKey;
    }

    public function withdraw(int $quantity): void
    {
        if (!$this->isActive()) {
            throw new InactiveProductException('El producto está dado de baja.');
        }

        if ($quantity <= 0) {
            throw new BusinessRuleViolation('La cantidad a retirar debe ser mayor que cero.');
        }

        if ($this->stock < $quantity) {
            throw new InsufficientStockException("Stock insuficiente para el producto '{$this->name}'. Disponible: {$this->stock}, solicitado: {$quantity}.");
        }

        $this->stock -= $quantity;
    }

    public function restock(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new BusinessRuleViolation('La cantidad a reponer debe ser mayor que cero.');
        }
        $this->stock += $quantity;
    }

    public function softDelete(DateTimeImmutable $at): void
    {
        if ($this->deletedAt !== null) {
            throw new BusinessRuleViolation('El producto ya se encuentra dado de baja.');
        }
        $this->deletedAt = $at;
    }
}
