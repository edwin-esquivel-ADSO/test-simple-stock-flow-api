<?php

declare(strict_types=1);

namespace App\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->attributes->get('auth_user');

        if ($user === null) {
            return response('', 401, [
                'Content-Length' => '0',
                'WWW-Authenticate' => 'Bearer',
            ]);
        }

        $userRole = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!in_array($userRole, $roles, true)) {
            // CA-07.4 / P-38: Un token de seller en operacion de admin responde 403 con cuerpo vacío (Content-Length: 0)
            return response('', 403, [
                'Content-Length' => '0',
            ]);
        }

        return $next($request);
    }
}
