<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

interface FileStorage
{
    public function store(string $contents, string $extension): string;

    public function get(string $key): ?string;

    public function delete(string $key): void;
}
