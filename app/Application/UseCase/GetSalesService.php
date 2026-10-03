<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Ports\Inbound\GetSales;
use App\Application\Ports\Inbound\SaleView;
use App\Application\Ports\Inbound\SaleItemView;
use App\Application\Ports\Inbound\PagedResult;
use App\Application\Ports\Outbound\SaleRepository;
use App\Application\Model\PageRequest;
use App\Domain\Exception\BusinessRuleViolation;

final class GetSalesService implements GetSales
{
    private SaleRepository $saleRepository;

    public function __construct(SaleRepository $saleRepository)
    {
        $this->saleRepository = $saleRepository;
    }

    public function listSales(PageRequest $pageRequest): PagedResult
    {
        return $this->saleRepository->listPaginated($pageRequest);
    }

    public function getSale(string $id): SaleView
    {
        $sale = $this->saleRepository->findById($id);
        if ($sale === null) {
            throw new BusinessRuleViolation("Venta no encontrada con id '{$id}'.");
        }

        $items = [];
        foreach ($sale->getItems() as $item) {
            $items[] = new SaleItemView(
                $item->getId(),
                $item->getProductId(),
                $item->getProductName(),
                $item->getCategoryName(),
                $item->getQuantity()->getValue(),
                $item->getUnitPrice()->toFloat(),
                $item->getSubtotal()->toFloat()
            );
        }

        return new SaleView(
            $sale->getId(),
            $sale->getSoldAt(),
            $sale->getSoldByUsername()->getValue(),
            $sale->getTotal()->toFloat(),
            $items
        );
    }
}
