<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

use App\Domain\Model\User;
use App\Domain\ValueObject\Username;

interface UserRepository
{
    public function findById(string $id): ?User;

    public function findByUsername(Username $username): ?User;

    public function save(User $user): void;
}
