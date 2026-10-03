<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

use App\Domain\Model\Sale;
use App\Application\Ports\Inbound\PagedResult;
use App\Application\Model\PageRequest;

interface SaleRepository
{
    public function save(Sale $sale): void;

    public function findById(string $id): ?Sale;

    public function listPaginated(PageRequest $pageRequest): PagedResult;
}
