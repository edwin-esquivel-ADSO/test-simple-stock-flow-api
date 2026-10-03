<?php

declare(strict_types=1);

namespace App\Application\Ports\Inbound;

final class SalesReportRow
{
    private string $productId;
    private string $productName;
    private string $categoryName;
    private int $unitsSold;
    private float $revenue;

    public function __construct(
        string $productId,
        string $productName,
        string $categoryName,
        int $unitsSold,
        float $revenue
    ) {
        $this->productId = $productId;
        $this->productName = $productName;
        $this->categoryName = $categoryName;
        $this->unitsSold = $unitsSold;
        $this->revenue = $revenue;
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

    public function getUnitsSold(): int
    {
        return $this->unitsSold;
    }

    public function getRevenue(): float
    {
        return $this->revenue;
    }
}
