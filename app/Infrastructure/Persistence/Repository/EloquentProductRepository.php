<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Application\Ports\Outbound\ProductRepository;
use App\Application\Ports\Inbound\PagedResult;
use App\Application\Ports\Inbound\ProductView;
use App\Application\Model\PageRequest;
use App\Application\Exception\ConcurrencyConflict;
use App\Domain\Model\Product;
use App\Infrastructure\Persistence\Model\ProductModel;
use App\Infrastructure\Persistence\Model\SaleItemModel;
use App\Infrastructure\Persistence\Mapper\ProductMapper;

final class EloquentProductRepository implements ProductRepository
{
    public function findById(string $id): ?Product
    {
        $model = ProductModel::find($id);
        return $model !== null ? ProductMapper::toDomain($model) : null;
    }

    public function findByIds(array $ids): array
    {
        $models = ProductModel::whereIn('id', $ids)->get();
        $result = [];
        foreach ($models as $model) {
            $result[$model->id] = ProductMapper::toDomain($model);
        }
        return $result;
    }

    public function save(Product $product): void
    {
        $existing = ProductModel::find($product->getId());

        if ($existing === null) {
            ProductModel::create([
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice()->getAmount()->__toString(),
                'stock' => $product->getStock(),
                'category_id' => $product->getCategoryId(),
                'image_key' => $product->getImageKey(),
                'deleted_at' => $product->getDeletedAt()?->format('Y-m-d H:i:s.u'),
                'version' => 1,
            ]);
            return;
        }

        $currentVersion = (int) $existing->version;
        $updated = ProductModel::where('id', $product->getId())
            ->where('version', $currentVersion)
            ->update([
                'name' => $product->getName(),
                'price' => $product->getPrice()->getAmount()->__toString(),
                'stock' => $product->getStock(),
                'category_id' => $product->getCategoryId(),
                'image_key' => $product->getImageKey(),
                'deleted_at' => $product->getDeletedAt()?->format('Y-m-d H:i:s.u'),
                'version' => $currentVersion + 1,
            ]);

        if ($updated === 0) {
            throw new ConcurrencyConflict("Conflicto de concurrencia al actualizar el producto '{$product->getName()}'.");
        }
    }

    public function search(?string $categoryId, PageRequest $pageRequest): PagedResult
    {
        $query = ProductModel::with('category')->whereNull('deleted_at');

        if ($categoryId !== null && trim($categoryId) !== '') {
            $query->where('category_id', $categoryId);
        }

        $totalItems = $query->count();
        $models = $query->orderBy('name', 'asc')
            ->offset($pageRequest->getOffset())
            ->limit($pageRequest->getPerPage())
            ->get();

        $items = [];
        foreach ($models as $model) {
            $categoryName = $model->category !== null ? $model->category->name : 'Desconocida';
            $imageUrl = $model->image_key !== null ? "/media/{$model->image_key}" : null;
            $items[] = new ProductView(
                $model->id,
                $model->name,
                (float) $model->price,
                (int) $model->stock,
                $model->category_id,
                $categoryName,
                $imageUrl,
                $model->deleted_at === null
            );
        }

        return new PagedResult($items, $totalItems, $pageRequest->getPage(), $pageRequest->getPerPage());
    }

    public function hasActiveSales(string $productId): bool
    {
        return SaleItemModel::where('product_id', $productId)->exists();
    }
}
