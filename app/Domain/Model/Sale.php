<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\ValueObject\Username;
use App\Domain\ValueObject\Money;
use App\Domain\Exception\EmptySaleException;
use App\Domain\Exception\RepeatedProductException;
use DateTimeImmutable;

final class Sale
{
    private string $id;
    private DateTimeImmutable $soldAt;
    private Username $soldByUsername;
    private string $soldByUserId;
    /** @var SaleItem[] */
    private array $items;

    /**
     * @param SaleItem[] $items
     */
    public function __construct(
        string $id,
        DateTimeImmutable $soldAt,
        Username $soldByUsername,
        string $soldByUserId,
        array $items
    ) {
        if (count($items) === 0) {
            throw new EmptySaleException('Una venta debe tener al menos una línea.');
        }

        $seenProductIds = [];
        foreach ($items as $item) {
            $prodId = $item->getProductId();
            if (isset($seenProductIds[$prodId])) {
                throw new RepeatedProductException("El producto '{$item->getProductName()}' se encuentra repetido en la venta.");
            }
            $seenProductIds[$prodId] = true;
        }

        $this->id = $id;
        $this->soldAt = $soldAt;
        $this->soldByUsername = $soldByUsername;
        $this->soldByUserId = $soldByUserId;
        $this->items = array_values($items);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSoldAt(): DateTimeImmutable
    {
        return $this->soldAt;
    }

    public function getSoldByUsername(): Username
    {
        return $this->soldByUsername;
    }

    public function getSoldByUserId(): string
    {
        return $this->soldByUserId;
    }

    /**
     * @return SaleItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotal(): Money
    {
        $total = Money::zero('COP');
        foreach ($this->items as $item) {
            $total = $total->add($item->getSubtotal());
        }
        return $total;
    }
}
