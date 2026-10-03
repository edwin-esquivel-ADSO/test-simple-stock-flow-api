<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller;

use Symfony\Component\HttpFoundation\Response;
use App\Application\Ports\Inbound\ManageProducts;
use Illuminate\Routing\Controller;

final class CategoryController extends Controller
{
    private ManageProducts $manageProducts;

    public function __construct(ManageProducts $manageProducts)
    {
        $this->manageProducts = $manageProducts;
    }

    public function index(): Response
    {
        $categories = $this->manageProducts->listCategories();
        return response()->json($categories, 200);
    }
}
