<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Ports\Inbound\PlaceSale;
use App\Application\Ports\Inbound\PlaceSaleCommand;
use App\Application\Ports\Inbound\SaleView;
use App\Application\Ports\Inbound\SaleItemView;
use App\Application\Ports\Outbound\ProductRepository;
use App\Application\Ports\Outbound\CategoryRepository;
use App\Application\Ports\Outbound\SaleRepository;
use App\Application\Ports\Outbound\UnitOfWork;
use App\Application\Ports\Outbound\Clock;
use App\Application\Exception\ConcurrencyConflict;
use App\Domain\Model\Sale;
use App\Domain\Model\SaleItem;
use App\Domain\ValueObject\Quantity;
use App\Domain\ValueObject\Username;
use App\Domain\Exception\ProductNotFoundException;
use Ramsey\Uuid\Uuid;

final class PlaceSaleService implements PlaceSale
{
    private ProductRepository $productRepository;
    private CategoryRepository $categoryRepository;
    private SaleRepository $saleRepository;
    private UnitOfWork $unitOfWork;
    private Clock $clock;

    public function __construct(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        SaleRepository $saleRepository,
        UnitOfWork $unitOfWork,
        Clock $clock
    ) {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->saleRepository = $saleRepository;
        $this->unitOfWork = $unitOfWork;
        $this->clock = $clock;
    }

    public function execute(PlaceSaleCommand $command): SaleView
    {
        $maxRetries = 3;
        $attempt = 0;

        while (true) {
            $attempt++;
            try {
                return $this->unitOfWork->run(function () use ($command): SaleView {
                    $saleId = Uuid::uuid4()->toString();
                    $soldAt = $this->clock->now();
                    $username = new Username($command->getSoldByUsername());

                    $saleItems = [];
                    $viewItems = [];

                    foreach ($command->getItems() as $itemCmd) {
                        $product = $this->productRepository->findById($itemCmd->getProductId());
                        if ($product === null) {
                            throw new ProductNotFoundException("Producto no encontrado con id '{$itemCmd->getProductId()}'.");
                        }

                        // RN-01: withdraw valide stock y regla de negocio
                        $product->withdraw($itemCmd->getQuantity());

                        $category = $this->categoryRepository->findById($product->getCategoryId());
                        $categoryName = $category !== null ? $category->getName() : 'General';

                        // RN-06: congelar precio, nombre y categoría
                        $itemId = Uuid::uuid4()->toString();
                        $quantityVO = new Quantity($itemCmd->getQuantity());

                        $saleItem = new SaleItem(
                            $itemId,
                            $saleId,
                            $product->getId(),
                            $product->getName(),
                            $categoryName,
                            $quantityVO,
                            $product->getPrice()
                        );

                        $saleItems[] = $saleItem;
                        $viewItems[] = new SaleItemView(
                            $saleItem->getId(),
                            $saleItem->getProductId(),
                            $saleItem->getProductName(),
                            $saleItem->getCategoryName(),
                            $saleItem->getQuantity()->getValue(),
                            $saleItem->getUnitPrice()->toFloat(),
                            $saleItem->getSubtotal()->toFloat()
                        );

                        // Persistir descuento de stock con verificación de versión
                        $this->productRepository->save($product);
                    }

                    // RN-04, RN-05, RN-12 validadas por el agregado Sale
                    $sale = new Sale(
                        $saleId,
                        $soldAt,
                        $username,
                        $command->getSoldByUserId(),
                        $saleItems
                    );

                    $this->saleRepository->save($sale);

                    return new SaleView(
                        $sale->getId(),
                        $sale->getSoldAt(),
                        $sale->getSoldByUsername()->getValue(),
                        $sale->getTotal()->toFloat(),
                        $viewItems
                    );
                });
            } catch (ConcurrencyConflict $e) {
                if ($attempt >= $maxRetries) {
                    throw $e;
                }
                // Backoff mínimo antes de reintentar desde la lectura
                usleep(50000 * $attempt);
            }
        }
    }
}
