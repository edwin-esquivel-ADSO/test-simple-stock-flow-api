<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

use App\Domain\Model\User;
use App\Application\Ports\Inbound\AuthResult;

interface TokenGenerator
{
    public function generate(User $user): AuthResult;

    /**
     * @return array{sub: string, unique_name: string, role: string, jti: string, exp: int}|null
     */
    public function verify(string $token): ?array;
}
