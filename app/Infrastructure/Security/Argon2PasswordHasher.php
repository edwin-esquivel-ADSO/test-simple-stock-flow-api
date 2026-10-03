<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Ports\Outbound\PasswordHasher;

final class Argon2PasswordHasher implements PasswordHasher
{
    public function hash(string $plainPassword): string
    {
        // Usa PASSWORD_DEFAULT (BCRYPT / ARGON2) nativo de PHP
        return password_hash($plainPassword, PASSWORD_DEFAULT);
    }

    public function verify(string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }
}
