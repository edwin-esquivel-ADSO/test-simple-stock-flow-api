<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mapper;

use App\Domain\Model\Sale;
use App\Domain\Model\SaleItem;
use App\Domain\ValueObject\Quantity;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Username;
use App\Infrastructure\Persistence\Model\SaleModel;
use App\Infrastructure\Persistence\Model\SaleItemModel;
use DateTimeImmutable;

final class SaleMapper
{
    public static function toDomain(SaleModel $model): Sale
    {
        $soldAt = $model->sold_at instanceof \DateTimeInterface
            ? DateTimeImmutable::createFromInterface($model->sold_at)
            : new DateTimeImmutable((string) $model->sold_at);

        $items = [];
        foreach ($model->items as $itemModel) {
            $items[] = new SaleItem(
                (string) $itemModel->id,
                (string) $itemModel->sale_id,
                (string) $itemModel->product_id,
                (string) $itemModel->product_name,
                (string) $itemModel->category_name,
                new Quantity((int) $itemModel->quantity),
                Money::of((string) $itemModel->unit_price, 'COP')
            );
        }

        return new Sale(
            (string) $model->id,
            $soldAt,
            new Username((string) $model->sold_by_username),
            (string) $model->sold_by_user_id,
            $items
        );
    }
}
