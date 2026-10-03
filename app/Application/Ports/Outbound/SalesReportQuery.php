<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

use App\Application\Model\DateRange;
use App\Application\Ports\Inbound\SalesReport;

interface SalesReportQuery
{
    public function getReport(DateRange $range): SalesReport;
}
