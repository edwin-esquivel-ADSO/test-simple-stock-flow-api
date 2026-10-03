<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Application\Ports\Outbound\UserRepository;
use App\Domain\Model\User;
use App\Domain\ValueObject\Username;
use App\Infrastructure\Persistence\Model\UserModel;
use App\Infrastructure\Persistence\Mapper\UserMapper;

final class EloquentUserRepository implements UserRepository
{
    public function findById(string $id): ?User
    {
        $model = UserModel::find($id);
        return $model !== null ? UserMapper::toDomain($model) : null;
    }

    public function findByUsername(Username $username): ?User
    {
        $model = UserModel::where('username', $username->getValue())->first();
        return $model !== null ? UserMapper::toDomain($model) : null;
    }

    public function save(User $user): void
    {
        UserModel::updateOrCreate(
            ['id' => $user->getId()],
            [
                'username' => $user->getUsername()->getValue(),
                'password_hash' => $user->getPasswordHash(),
                'role' => $user->getRole()->getValue(),
            ]
        );
    }
}
