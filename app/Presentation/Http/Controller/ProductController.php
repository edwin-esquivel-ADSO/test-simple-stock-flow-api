<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Application\Ports\Inbound\ManageProducts;
use App\Application\Model\PageRequest;
use App\Application\Ports\Inbound\ProductView;
use Illuminate\Routing\Controller;
use Ramsey\Uuid\Uuid;

final class ProductController extends Controller
{
    private ManageProducts $manageProducts;

    public function __construct(ManageProducts $manageProducts)
    {
        $this->manageProducts = $manageProducts;
    }

    public function index(Request $request): Response
    {
        $rawSize = $request->query('size', 20);
        if (!is_numeric($rawSize)) {
            return response()->json([
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'El parámetro size debe ser un entero.',
                'errors' => ['size' => ['Debe ser un número entero.']]
            ], 400);
        }

        $size = (int) $rawSize;
        if ($size === 0) {
            $size = 20; // P-30
        }
        $size = min(100, $size); // P-29

        $page = max(1, (int) $request->query('page', 1));
        $categoryId = $request->query('categoryId');
        if ($categoryId !== null) {
            $categoryId = (string) $categoryId;
        }

        $pageRequest = new PageRequest($page, $size);
        $result = $this->manageProducts->listProducts($categoryId, $pageRequest);

        $items = array_map(fn(ProductView $pv) => $this->serializeProductView($pv), $result->getItems());

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

        $productView = $this->manageProducts->getProduct($id);
        return response()->json($this->serializeProductView($productView), 200);
    }

    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'categoryId' => 'required|string',
        ]);

        $productView = $this->manageProducts->createProduct(
            (string) $validated['name'],
            (float) $validated['price'],
            (int) $validated['stock'],
            (string) $validated['categoryId']
        );

        return response()->json($this->serializeProductView($productView), 201);
    }

    public function update(Request $request, string $id): Response
    {
        if (!Uuid::isValid($id)) {
            return response('', 404, ['Content-Length' => '0']);
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'categoryId' => 'required|string',
        ]);

        $productView = $this->manageProducts->updateProduct(
            $id,
            (string) $validated['name'],
            (float) $validated['price'],
            (int) $validated['stock'],
            (string) $validated['categoryId']
        );

        return response()->json($this->serializeProductView($productView), 200);
    }

    public function destroy(string $id): Response
    {
        if (!Uuid::isValid($id)) {
            return response('', 404, ['Content-Length' => '0']);
        }

        $this->manageProducts->deleteProduct($id);
        return response('', 204);
    }

    public function uploadImage(Request $request, string $id): Response
    {
        if (!Uuid::isValid($id)) {
            return response('', 404, ['Content-Length' => '0']);
        }

        if (!$request->hasFile('image')) {
            return response()->json([
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'El archivo de imagen es obligatorio.',
                'errors' => ['image' => ['Se requiere un archivo de imagen válido.']]
            ], 400);
        }

        $file = $request->file('image');
        $binary = file_get_contents($file->getRealPath());
        $ext = $file->getClientOriginalExtension() ?: 'jpg';

        $productView = $this->manageProducts->uploadImage($id, $binary, $ext);
        return response()->json($this->serializeProductView($productView), 200);
    }

    private function serializeProductView(ProductView $pv): array
    {
        return [
            'id' => $pv->getId(),
            'name' => $pv->getName(),
            'price' => $pv->getPrice(),
            'stock' => $pv->getStock(),
            'categoryId' => $pv->getCategoryId(),
            'categoryName' => $pv->getCategoryName(),
            'imageUrl' => $pv->getImageUrl(), // Obligatorio presente aunque sea null (P-05)
            'isActive' => $pv->isActive(),
        ];
    }
}
