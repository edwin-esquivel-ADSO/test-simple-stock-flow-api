<?php

declare(strict_types=1);

namespace App\Application\Ports\Inbound;

final class SalesReport
{
    private string $from;
    private string $to;
    private int $salesCount;
    private float $grandTotal;
    private string $currency;
    /** @var SalesReportRow[] */
    private array $rows;

    /**
     * @param SalesReportRow[] $rows
     */
    public function __construct(
        string $from,
        string $to,
        int $salesCount,
        float $grandTotal,
        string $currency,
        array $rows
    ) {
        $this->from = $from;
        $this->to = $to;
        $this->salesCount = $salesCount;
        $this->grandTotal = $grandTotal;
        $this->currency = $currency !== '' ? $currency : 'COP';
        $this->rows = $rows;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getSalesCount(): int
    {
        return $this->salesCount;
    }

    public function getGrandTotal(): float
    {
        return $this->grandTotal;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return SalesReportRow[]
     */
    public function getRows(): array
    {
        return $this->rows;
    }
}
