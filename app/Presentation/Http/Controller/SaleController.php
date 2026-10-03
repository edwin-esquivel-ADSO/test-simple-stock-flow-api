<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Application\Ports\Inbound\PlaceSale;
use App\Application\Ports\Inbound\GetSales;
use App\Application\Ports\Inbound\PlaceSaleCommand;
use App\Application\Ports\Inbound\PlaceSaleItemCommand;
use App\Application\Ports\Inbound\SaleView;
use App\Application\Ports\Inbound\SaleItemView;
use App\Application\Model\PageRequest;
use App\Domain\Exception\EmptySaleException;
use Illuminate\Routing\Controller;
use Ramsey\Uuid\Uuid;

final class SaleController extends Controller
{
    private PlaceSale $placeSale;
    private GetSales $getSales;

    public function __construct(PlaceSale $placeSale, GetSales $getSales)
    {
        $this->placeSale = $placeSale;
        $this->getSales = $getSales;
    }

    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'lines' => 'required|array',
            'lines.*.productId' => 'required|string',
            'lines.*.quantity' => 'required|integer',
        ]);

        if (count($validated['lines']) === 0) {
            throw new EmptySaleException('La venta debe tener al menos un ítem.'); // P-07
        }

        $user = $request->attributes->get('auth_user');
        $userId = is_array($user) ? ($user['sub'] ?? '') : ($user->sub ?? '');
        $username = is_array($user) ? ($user['unique_name'] ?? '') : ($user->unique_name ?? '');

        $items = [];
        foreach ($validated['lines'] as $line) {
            $items[] = new PlaceSaleItemCommand(
                (string) $line['productId'],
                (int) $line['quantity']
            );
        }

        $command = new PlaceSaleCommand($userId, $username, $items);
        $saleView = $this->placeSale->execute($command);

        return response()->json($this->serializeSaleView($saleView), 201);
    }

    public function index(Request $request): Response
    {
        $rawSize = $request->query('size', 20);
        $size = is_numeric($rawSize) ? min(100, max(1, (int) $rawSize)) : 20;
        $page = max(1, (int) $request->query('page', 1));

        $pageRequest = new PageRequest($page, $size);
        $result = $this->getSales->listSales($pageRequest);

        $items = array_map(fn(SaleView $sv) => $this->serializeSaleView($sv), $result->getItems());

        return response()->json([
            'items' => $items,
            'page' => $result->getPage(),
            'size' => $result->getPerPage(),
            'total' => $result->getTotalItems(),
            'totalPages' => $result->getTotalPages(),
        ], 200);
    }

    public function show(string $id): Response
    {
        if (!Uuid::isValid($id)) {
            return response('', 404, ['Content-Length' => '0']); // P-06
        }

        $saleView = $this->getSales->getSale($id);
        return response()->json($this->serializeSaleView($saleView), 200);
    }

    private function serializeSaleView(SaleView $sv): array
    {
        $items = [];
        foreach ($sv->getItems() as $item) {
            $items[] = [
                'id' => $item->getId(),
                'productId' => $item->getProductId(),
                'productName' => $item->getProductName(),
                'categoryName' => $item->getCategoryName(),
                'quantity' => $item->getQuantity(),
                'unitPrice' => $item->getUnitPrice(),
                'subtotal' => $item->getSubtotal(),
            ];
        }

        return [
            'id' => $sv->getId(),
            'soldAt' => $sv->getSoldAt()->format('Y-m-d\TH:i:sP'),
            'soldByUsername' => $sv->getSoldByUsername(),
            'total' => $sv->getTotal(),
            'items' => $items,
        ];
    }
}
