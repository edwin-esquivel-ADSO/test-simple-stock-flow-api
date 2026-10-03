<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Application\Ports\Outbound\SaleRepository;
use App\Application\Ports\Inbound\PagedResult;
use App\Application\Ports\Inbound\SaleView;
use App\Application\Ports\Inbound\SaleItemView;
use App\Application\Model\PageRequest;
use App\Domain\Model\Sale;
use App\Infrastructure\Persistence\Model\SaleModel;
use App\Infrastructure\Persistence\Model\SaleItemModel;
use App\Infrastructure\Persistence\Mapper\SaleMapper;
use DateTimeImmutable;

final class EloquentSaleRepository implements SaleRepository
{
    public function save(Sale $sale): void
    {
        SaleModel::create([
            'id' => $sale->getId(),
            'sold_at' => $sale->getSoldAt()->format('Y-m-d H:i:s.u'),
            'sold_by_username' => $sale->getSoldByUsername()->getValue(),
            'sold_by_user_id' => $sale->getSoldByUserId(),
        ]);

        foreach ($sale->getItems() as $item) {
            SaleItemModel::create([
                'id' => $item->getId(),
                'sale_id' => $item->getSaleId(),
                'product_id' => $item->getProductId(),
                'product_name' => $item->getProductName(),
                'category_name' => $item->getCategoryName(),
                'quantity' => $item->getQuantity()->getValue(),
                'unit_price' => $item->getUnitPrice()->getAmount()->__toString(),
            ]);
        }
    }

    public function findById(string $id): ?Sale
    {
        $model = SaleModel::with('items')->find($id);
        return $model !== null ? SaleMapper::toDomain($model) : null;
    }

    public function listPaginated(PageRequest $pageRequest): PagedResult
    {
        $query = SaleModel::with('items');
        $totalItems = $query->count();

        $models = $query->orderBy('sold_at', 'desc')
            ->offset($pageRequest->getOffset())
            ->limit($pageRequest->getPerPage())
            ->get();

        $items = [];
        foreach ($models as $model) {
            $sale = SaleMapper::toDomain($model);
            $itemViewList = [];
            foreach ($sale->getItems() as $item) {
                $itemViewList[] = new SaleItemView(
                    $item->getId(),
                    $item->getProductId(),
                    $item->getProductName(),
                    $item->getCategoryName(),
                    $item->getQuantity()->getValue(),
                    $item->getUnitPrice()->toFloat(),
                    $item->getSubtotal()->toFloat()
                );
            }

            $items[] = new SaleView(
                $sale->getId(),
                $sale->getSoldAt(),
                $sale->getSoldByUsername()->getValue(),
                $sale->getTotal()->toFloat(),
                $itemViewList
            );
        }

        return new PagedResult($items, $totalItems, $pageRequest->getPage(), $pageRequest->getPerPage());
    }
}
