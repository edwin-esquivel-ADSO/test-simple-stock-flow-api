<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use App\Domain\Exception\InvalidPriceException;

final class Money
{
    private BigDecimal $amount;
    private string $currency;

    private function __construct(BigDecimal $amount, string $currency = 'COP')
    {
        if ($amount->isNegative()) {
            throw new InvalidPriceException('El precio no puede ser negativo.');
        }

        $this->amount = $amount->toScale(2, RoundingMode::HALF_UP);
        $this->currency = $currency;
    }

    public static function of(string|int|float|BigDecimal $amount, string $currency = 'COP'): self
    {
        $bigDecimal = $amount instanceof BigDecimal ? $amount : BigDecimal::of((string) $amount);
        return new self($bigDecimal, $currency);
    }

    public static function zero(string $currency = 'COP'): self
    {
        return new self(BigDecimal::zero(), $currency);
    }

    public function add(self $other): self
    {
        return new self($this->amount->plus($other->amount), $this->currency);
    }

    public function multiply(int $quantity): self
    {
        return new self($this->amount->multipliedBy($quantity), $this->currency);
    }

    public function getAmount(): BigDecimal
    {
        return $this->amount;
    }

    public function toFloat(): float
    {
        return $this->amount->toFloat();
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function equals(self $other): bool
    {
        return $this->amount->isEqualTo($other->amount) && $this->currency === $other->currency;
    }

    public function isPositive(): bool
    {
        return $this->amount->isPositive();
    }
}
