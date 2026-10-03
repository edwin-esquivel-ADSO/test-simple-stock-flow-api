<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Ports\Inbound\GetSalesReport;
use App\Application\Ports\Inbound\SalesReport;
use App\Application\Ports\Outbound\SalesReportQuery;
use App\Application\Model\DateRange;

final class SalesReportService implements GetSalesReport
{
    private SalesReportQuery $salesReportQuery;

    public function __construct(SalesReportQuery $salesReportQuery)
    {
        $this->salesReportQuery = $salesReportQuery;
    }

    public function execute(DateRange $range): SalesReport
    {
        return $this->salesReportQuery->getReport($range);
    }
}
