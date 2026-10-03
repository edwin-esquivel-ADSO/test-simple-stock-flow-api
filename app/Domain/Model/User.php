<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\ValueObject\Username;
use App\Domain\ValueObject\Role;
use App\Domain\Exception\BusinessRuleViolation;

final class User
{
    private string $id;
    private Username $username;
    private string $passwordHash;
    private Role $role;

    public function __construct(string $id, Username $username, string $passwordHash, Role $role)
    {
        if (trim($passwordHash) === '') {
            throw new BusinessRuleViolation('El hash de contraseña no puede estar vacío.');
        }

        $this->id = $id;
        $this->username = $username;
        $this->passwordHash = $passwordHash;
        $this->role = $role;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUsername(): Username
    {
        return $this->username;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    public function isSeller(): bool
    {
        return $this->role->isSeller();
    }
}
