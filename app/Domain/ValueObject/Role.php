<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidRoleException;

final class Role
{
    public const ADMIN = 'admin';
    public const SELLER = 'seller';

    private string $value;

    public function __construct(string $value)
    {
        $normalized = strtolower(trim($value));
        if ($normalized !== self::ADMIN && $normalized !== self::SELLER) {
            throw new InvalidRoleException('El rol debe ser admin o seller.');
        }
        $this->value = $normalized;
    }

    public static function admin(): self
    {
        return new self(self::ADMIN);
    }

    public static function seller(): self
    {
        return new self(self::SELLER);
    }

    public static function of(string $value): self
    {
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isAdmin(): bool
    {
        return $this->value === self::ADMIN;
    }

    public function isSeller(): bool
    {
        return $this->value === self::SELLER;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
