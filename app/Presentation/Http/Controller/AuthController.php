<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controller;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Application\Ports\Inbound\Authenticate;
use Illuminate\Routing\Controller;

final class AuthController extends Controller
{
    private Authenticate $authenticate;

    public function __construct(Authenticate $authenticate)
    {
        $this->authenticate = $authenticate;
    }

    public function login(Request $request): Response
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $result = $this->authenticate->login(
            (string) $validated['username'],
            (string) $validated['password']
        );

        return response()->json([
            'accessToken' => $result->getAccessToken(),
            'expiresAt' => $result->getExpiresAt()->format('Y-m-d\TH:i:sP'),
            'username' => $result->getUsername(),
            'role' => $result->getRole(),
        ], 200);
    }

    public function register(Request $request): Response
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $this->authenticate->registerSeller(
            (string) $validated['username'],
            (string) $validated['password']
        );

        // 201 Created sin Location header (P-18 / CA-07)
        return response('', 201);
    }
}
