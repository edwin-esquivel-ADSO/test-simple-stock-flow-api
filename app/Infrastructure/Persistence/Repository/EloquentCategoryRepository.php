<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Application\Ports\Outbound\CategoryRepository;
use App\Domain\Model\Category;
use App\Infrastructure\Persistence\Model\CategoryModel;
use App\Infrastructure\Persistence\Mapper\CategoryMapper;

final class EloquentCategoryRepository implements CategoryRepository
{
    public function findAll(): array
    {
        $models = CategoryModel::orderBy('name', 'asc')->get();
        return $models->map(fn(CategoryModel $m) => CategoryMapper::toDomain($m))->all();
    }

    public function findById(string $id): ?Category
    {
        $model = CategoryModel::find($id);
        return $model !== null ? CategoryMapper::toDomain($model) : null;
    }
}
