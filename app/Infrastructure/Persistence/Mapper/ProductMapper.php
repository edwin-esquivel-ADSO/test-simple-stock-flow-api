<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mapper;

use App\Domain\Model\Product;
use App\Domain\ValueObject\Money;
use App\Infrastructure\Persistence\Model\ProductModel;
use DateTimeImmutable;

final class ProductMapper
{
    public static function toDomain(ProductModel $model): Product
    {
        $deletedAt = null;
        if ($model->deleted_at !== null) {
            $deletedAt = $model->deleted_at instanceof \DateTimeInterface
                ? DateTimeImmutable::createFromInterface($model->deleted_at)
                : new DateTimeImmutable((string) $model->deleted_at);
        }

        return new Product(
            (string) $model->id,
            (string) $model->name,
            Money::of((string) $model->price, 'COP'),
            (int) $model->stock,
            (string) $model->category_id,
            $model->image_key !== null ? (string) $model->image_key : null,
            $deletedAt
        );
    }
}
