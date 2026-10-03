<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Ports\Inbound\ManageProducts;
use App\Application\Ports\Inbound\ProductView;
use App\Application\Ports\Inbound\PagedResult;
use App\Application\Ports\Outbound\ProductRepository;
use App\Application\Ports\Outbound\CategoryRepository;
use App\Application\Ports\Outbound\FileStorage;
use App\Application\Ports\Outbound\Clock;
use App\Application\Model\PageRequest;
use App\Domain\Model\Product;
use App\Domain\ValueObject\Money;
use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Exception\UnknownCategoryException;
use Ramsey\Uuid\Uuid;

final class ProductCatalogService implements ManageProducts
{
    private ProductRepository $productRepository;
    private CategoryRepository $categoryRepository;
    private FileStorage $fileStorage;
    private Clock $clock;

    public function __construct(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        FileStorage $fileStorage,
        Clock $clock
    ) {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->fileStorage = $fileStorage;
        $this->clock = $clock;
    }

    public function listProducts(?string $categoryId, PageRequest $pageRequest): PagedResult
    {
        return $this->productRepository->search($categoryId, $pageRequest);
    }

    public function getProduct(string $id): ProductView
    {
        $product = $this->productRepository->findById($id);
        if ($product === null) {
            throw new ProductNotFoundException("Producto no encontrado con id '{$id}'.");
        }

        $category = $this->categoryRepository->findById($product->getCategoryId());
        $categoryName = $category !== null ? $category->getName() : 'Desconocida';
        $imageUrl = $product->getImageKey() !== null ? "/media/{$product->getImageKey()}" : null;

        return new ProductView(
            $product->getId(),
            $product->getName(),
            $product->getPrice()->toFloat(),
            $product->getStock(),
            $product->getCategoryId(),
            $categoryName,
            $imageUrl,
            $product->isActive()
        );
    }

    public function createProduct(string $name, float $price, int $stock, string $categoryId): ProductView
    {
        $category = $this->categoryRepository->findById($categoryId);
        if ($category === null) {
            throw new UnknownCategoryException("Categoría no encontrada con id '{$categoryId}'.");
        }

        $id = Uuid::uuid4()->toString();
        $money = Money::of($price, 'COP');
        $product = new Product($id, $name, $money, $stock, $categoryId);

        $this->productRepository->save($product);

        return new ProductView(
            $product->getId(),
            $product->getName(),
            $product->getPrice()->toFloat(),
            $product->getStock(),
            $product->getCategoryId(),
            $category->getName(),
            null,
            true
        );
    }

    public function updateProduct(string $id, string $name, float $price, int $stock, string $categoryId): ProductView
    {
        $product = $this->productRepository->findById($id);
        if ($product === null) {
            throw new ProductNotFoundException("Producto no encontrado con id '{$id}'.");
        }

        $category = $this->categoryRepository->findById($categoryId);
        if ($category === null) {
            throw new UnknownCategoryException("Categoría no encontrada con id '{$categoryId}'.");
        }

        $product->rename($name);
        $product->changePrice(Money::of($price, 'COP'));
        $product->updateCategory($categoryId);

        // Actualizar stock si es necesario
        $currentStock = $product->getStock();
        if ($stock > $currentStock) {
            $product->restock($stock - $currentStock);
        } elseif ($stock < $currentStock) {
            $product->withdraw($currentStock - $stock);
        }

        $this->productRepository->save($product);

        $imageUrl = $product->getImageKey() !== null ? "/media/{$product->getImageKey()}" : null;

        return new ProductView(
            $product->getId(),
            $product->getName(),
            $product->getPrice()->toFloat(),
            $product->getStock(),
            $product->getCategoryId(),
            $category->getName(),
            $imageUrl,
            $product->isActive()
        );
    }

    public function deleteProduct(string $id): void
    {
        $product = $this->productRepository->findById($id);
        if ($product === null) {
            throw new ProductNotFoundException("Producto no encontrado con id '{$id}'.");
        }

        $product->softDelete($this->clock->now());
        $this->productRepository->save($product);
    }

    public function uploadImage(string $id, string $imageBinary, string $extension): ProductView
    {
        $product = $this->productRepository->findById($id);
        if ($product === null) {
            throw new ProductNotFoundException("Producto no encontrado con id '{$id}'.");
        }

        if ($product->getImageKey() !== null) {
            $this->fileStorage->delete($product->getImageKey());
        }

        $key = $this->fileStorage->store($imageBinary, $extension);
        $product->setImageKey($key);

        $this->productRepository->save($product);

        $category = $this->categoryRepository->findById($product->getCategoryId());
        $categoryName = $category !== null ? $category->getName() : 'Desconocida';

        return new ProductView(
            $product->getId(),
            $product->getName(),
            $product->getPrice()->toFloat(),
            $product->getStock(),
            $product->getCategoryId(),
            $categoryName,
            "/media/{$key}",
            $product->isActive()
        );
    }

    public function getImage(string $key): ?string
    {
        return $this->fileStorage->get($key);
    }

    public function listCategories(): array
    {
        $categories = $this->categoryRepository->findAll();
        $result = [];
        foreach ($categories as $cat) {
            $result[] = [
                'id' => $cat->getId(),
                'name' => $cat->getName(),
            ];
        }
        return $result;
    }
}
