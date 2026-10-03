<?php

declare(strict_types=1);

namespace App\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Application\Ports\Outbound\TokenGenerator;

final class AuthenticateToken
{
    private TokenGenerator $tokenGenerator;

    public function __construct(TokenGenerator $tokenGenerator)
    {
        $this->tokenGenerator = $tokenGenerator;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');

        if (!str_starts_with($header, 'Bearer ')) {
            return response('', 401, [
                'Content-Length' => '0',
                'WWW-Authenticate' => 'Bearer',
            ]);
        }

        $token = substr($header, 7);
        $payload = $this->tokenGenerator->verify($token);

        if ($payload === null) {
            return response('', 401, [
                'Content-Length' => '0',
                'WWW-Authenticate' => 'Bearer error="invalid_token"',
            ]);
        }

        $request->attributes->set('auth_user', $payload);

        return $next($request);
    }
}
