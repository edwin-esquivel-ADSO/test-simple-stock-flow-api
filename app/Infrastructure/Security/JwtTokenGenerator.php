<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Ports\Outbound\TokenGenerator;
use App\Application\Ports\Inbound\AuthResult;
use App\Domain\Model\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Throwable;

final class JwtTokenGenerator implements TokenGenerator
{
    private string $signingKey;
    private int $ttlMinutes;

    public function __construct(string $signingKey = '', int $ttlMinutes = 60)
    {
        $this->signingKey = $signingKey !== '' ? $signingKey : (string) env('JWT_SIGNING_KEY', 'simple-stock-flow-jwt-super-secret-key-minimum-32-bytes');
        $this->ttlMinutes = $ttlMinutes;
    }

    public function generate(User $user): AuthResult
    {
        $issuedAt = time();
        $expiresAtTimestamp = $issuedAt + ($this->ttlMinutes * 60);
        $expiresAt = (new DateTimeImmutable())->setTimestamp($expiresAtTimestamp);

        $payload = [
            'sub' => $user->getId(),
            'unique_name' => $user->getUsername()->getValue(),
            'role' => $user->getRole()->getValue(),
            'jti' => Uuid::uuid4()->toString(),
            'iat' => $issuedAt,
            'exp' => $expiresAtTimestamp,
        ];

        $token = JWT::encode($payload, $this->signingKey, 'HS256');

        return new AuthResult(
            $token,
            $expiresAt,
            $user->getUsername()->getValue(),
            $user->getRole()->getValue()
        );
    }

    public function verify(string $token): ?array
    {
        try {
            JWT::$leeway = 30; // 30 seconds leeway según api-contract
            $decoded = JWT::decode($token, new Key($this->signingKey, 'HS256'));
            return (array) $decoded;
        } catch (Throwable) {
            return null;
        }
    }
}
