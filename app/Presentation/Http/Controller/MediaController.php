<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller;

use Symfony\Component\HttpFoundation\Response;
use App\Application\Ports\Inbound\ManageProducts;
use Illuminate\Routing\Controller;

final class MediaController extends Controller
{
    private ManageProducts $manageProducts;

    public function __construct(ManageProducts $manageProducts)
    {
        $this->manageProducts = $manageProducts;
    }

    public function show(string $key): Response
    {
        $binary = $this->manageProducts->getImage($key);

        if ($binary === null) {
            // P-31 / P-34: 404 vacío con Content-Length: 0
            return response('', 404, ['Content-Length' => '0']);
        }

        $extension = strtolower(pathinfo($key, PATHINFO_EXTENSION));
        $contentType = match ($extension) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return response($binary, 200, [
            'Content-Type' => $contentType,
            'Content-Length' => (string) strlen($binary),
        ]);
    }
}
