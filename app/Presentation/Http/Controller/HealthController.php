<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Routing\Controller;

final class HealthController extends Controller
{
    public function check(): Response
    {
        return response()->json(['status' => 'ok'], 200);
    }
}
