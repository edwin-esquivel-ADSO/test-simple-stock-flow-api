<?php

declare(strict_types=1);

namespace App\Application\Ports\Inbound;

use DateTimeImmutable;

final class AuthResult
{
    private string $accessToken;
    private DateTimeImmutable $expiresAt;
    private string $username;
    private string $role;

    public function __construct(string $accessToken, DateTimeImmutable $expiresAt, string $username, string $role)
    {
        $this->accessToken = $accessToken;
        $this->expiresAt = $expiresAt;
        $this->username = $username;
        $this->role = $role;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getRole(): string
    {
        return $this->role;
    }
}
