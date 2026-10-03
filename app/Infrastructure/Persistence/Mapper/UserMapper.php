<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mapper;

use App\Domain\Model\User;
use App\Domain\ValueObject\Username;
use App\Domain\ValueObject\Role;
use App\Infrastructure\Persistence\Model\UserModel;

final class UserMapper
{
    public static function toDomain(UserModel $model): User
    {
        return new User(
            (string) $model->id,
            new Username((string) $model->username),
            (string) $model->password_hash,
            new Role((string) $model->role)
        );
    }
}
