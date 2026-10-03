<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Application\Ports\Inbound\GetSalesReport;
use App\Application\Ports\Inbound\SalesReport;
use App\Application\Ports\Inbound\SalesReportRow;
use App\Application\Model\DateRange;
use App\Domain\Exception\BusinessRuleViolation;
use Illuminate\Routing\Controller;
use DateTimeImmutable;
use Throwable;

final class ReportController extends Controller
{
    private GetSalesReport $getSalesReport;

    public function __construct(GetSalesReport $getSalesReport)
    {
        $this->getSalesReport = $getSalesReport;
    }

    public function sales(Request $request): Response
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $errors = [];
        if ($from === null || trim((string) $from) === '') {
            $errors['from'] = ['El parámetro from es obligatorio.'];
        }
        if ($to === null || trim((string) $to) === '') {
            $errors['to'] = ['El parámetro to es obligatorio.'];
        }

        if (!empty($errors)) {
            return response()->json([
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Parámetros obligatorios ausentes.',
                'errors' => $errors,
            ], 400); // P-10
        }

        $fromStr = (string) $from;
        $toStr = (string) $to;

        // Validar formato ISO 8601 con desplazamiento o Z (P-12, P-13, P-14, P-15)
        $isoPattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/';
        if (!preg_match($isoPattern, $fromStr)) {
            $errors['from'] = ['El parámetro from debe ser una fecha ISO 8601 con zona horaria o Z.'];
        }
        if (!preg_match($isoPattern, $toStr)) {
            $errors['to'] = ['El parámetro to debe ser una fecha ISO 8601 con zona horaria o Z.'];
        }

        if (!empty($errors)) {
            return response()->json([
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Formato de fecha inválido.',
                'errors' => $errors,
            ], 400);
        }

        try {
            $fromDate = new DateTimeImmutable($fromStr);
            $toDate = new DateTimeImmutable($toStr);
        } catch (Throwable) {
            return response()->json([
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Fecha no procesable.',
                'errors' => ['date' => ['Error al interpretar las fechas.']],
            ], 400);
        }

        if ($toDate <= $fromDate) {
            throw new BusinessRuleViolation('La fecha final no puede ser anterior a la inicial.'); // P-11
        }

        $range = new DateRange($fromDate, $toDate);
        $report = $this->getSalesReport->execute($range);

        $rows = array_map(function (SalesReportRow $row) {
            return [
                'productId' => $row->getProductId(),
                'productName' => $row->getProductName(),
                'categoryName' => $row->getCategoryName(),
                'unitsSold' => $row->getUnitsSold(),
                'revenue' => $row->getRevenue(),
            ];
        }, $report->getRows());

        return response()->json([
            'from' => $report->getFrom(),
            'to' => $report->getTo(),
            'salesCount' => $report->getSalesCount(),
            'grandTotal' => $report->getGrandTotal(),
            'currency' => $report->getCurrency(),
            'rows' => $rows,
        ], 200);
    }
}
