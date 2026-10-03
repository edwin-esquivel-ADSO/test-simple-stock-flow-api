<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

interface UnitOfWork
{
    /**
     * Executes the given operation within an atomic transaction.
     */
    public function run(callable $operation): mixed;
}
