<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Ports\Inbound\Authenticate;
use App\Application\Ports\Inbound\AuthResult;
use App\Application\Ports\Outbound\UserRepository;
use App\Application\Ports\Outbound\PasswordHasher;
use App\Application\Ports\Outbound\TokenGenerator;
use App\Domain\Model\User;
use App\Domain\ValueObject\Username;
use App\Domain\ValueObject\Role;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Exception\DuplicateUsernameException;
use Ramsey\Uuid\Uuid;

final class AuthenticationService implements Authenticate
{
    private UserRepository $userRepository;
    private PasswordHasher $passwordHasher;
    private TokenGenerator $tokenGenerator;

    public function __construct(
        UserRepository $userRepository,
        PasswordHasher $passwordHasher,
        TokenGenerator $tokenGenerator
    ) {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->tokenGenerator = $tokenGenerator;
    }

    public function login(string $username, string $password): AuthResult
    {
        $userVO = new Username($username);
        $user = $this->userRepository->findByUsername($userVO);

        if ($user === null || !$this->passwordHasher->verify($password, $user->getPasswordHash())) {
            throw new InvalidCredentialsException('Credenciales inválidas.');
        }

        return $this->tokenGenerator->generate($user);
    }

    public function registerSeller(string $username, string $password): void
    {
        $userVO = new Username($username);
        $existing = $this->userRepository->findByUsername($userVO);

        if ($existing !== null) {
            throw new DuplicateUsernameException("El nombre de usuario '{$userVO->getValue()}' ya se encuentra registrado.");
        }

        $passwordHash = $this->passwordHasher->hash($password);
        $userId = Uuid::uuid4()->toString();
        $user = new User($userId, $userVO, $passwordHash, Role::seller());

        $this->userRepository->save($user);
    }
}
