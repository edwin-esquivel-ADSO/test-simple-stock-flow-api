<?php

declare(strict_types=1);

namespace App\Application\Ports\Inbound;

interface Authenticate
{
    public function login(string $username, string $password): AuthResult;

    /**
     * DP-04: Admin creates seller. Structural restriction: cannot create admins.
     */
    public function registerSeller(string $username, string $password): void;
}
