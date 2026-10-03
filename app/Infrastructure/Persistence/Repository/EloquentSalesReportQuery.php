<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Application\Ports\Outbound\SalesReportQuery;
use App\Application\Ports\Inbound\SalesReport;
use App\Application\Ports\Inbound\SalesReportRow;
use App\Application\Model\DateRange;
use Illuminate\Support\Facades\DB;

final class EloquentSalesReportQuery implements SalesReportQuery
{
    public function getReport(DateRange $range): SalesReport
    {
        $fromStr = $range->getFrom()->format('Y-m-d H:i:s.u');
        $toStr = $range->getTo()->format('Y-m-d H:i:s.u');

        // Total sales count in range
        $salesCount = DB::table('sale')
            ->where('sold_at', '>=', $fromStr)
            ->where('sold_at', '<', $toStr)
            ->count();

        if ($salesCount === 0) {
            return new SalesReport(
                $range->getFrom()->format('Y-m-d\TH:i:s\Z'),
                $range->getTo()->format('Y-m-d\TH:i:s\Z'),
                0,
                0.0,
                'COP',
                []
            );
        }

        // Subquery to get the latest frozen product_name within range for DP-01
        $rowsData = DB::table('sale_item as si')
            ->join('sale as s', 's.id', '=', 'si.sale_id')
            ->where('s.sold_at', '>=', $fromStr)
            ->where('s.sold_at', '<', $toStr)
            ->select(
                'si.product_id',
                'si.category_name',
                DB::raw('SUBSTRING_INDEX(GROUP_CONCAT(si.product_name ORDER BY s.sold_at DESC SEPARATOR "|||"), "|||", 1) as latest_product_name'),
                DB::raw('SUM(si.quantity) as units_sold'),
                DB::raw('SUM(si.quantity * si.unit_price) as revenue')
            )
            ->groupBy('si.product_id', 'si.category_name')
            ->orderBy('revenue', 'desc')
            ->get();

        $rows = [];
        $grandTotal = 0.0;
        foreach ($rowsData as $r) {
            $rev = round((float) $r->revenue, 2);
            $grandTotal += $rev;
            $rows[] = new SalesReportRow(
                (string) $r->product_id,
                (string) $r->latest_product_name,
                (string) $r->category_name,
                (int) $r->units_sold,
                $rev
            );
        }

        return new SalesReport(
            $range->getFrom()->format('Y-m-d\TH:i:s\Z'),
            $range->getTo()->format('Y-m-d\TH:i:s\Z'),
            $salesCount,
            round($grandTotal, 2),
            'COP',
            $rows
        );
    }
}
