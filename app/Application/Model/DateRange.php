<?php

declare(strict_types=1);

namespace App\Application\Model;

use DateTimeImmutable;
use InvalidArgumentException;

final class DateRange
{
    private DateTimeImmutable $from;
    private DateTimeImmutable $to;

    public function __construct(DateTimeImmutable $from, DateTimeImmutable $to)
    {
        if ($to <= $from) {
            throw new InvalidArgumentException("La fecha final 'to' debe ser posterior a la fecha inicial 'from'.");
        }
        $this->from = $from;
        $this->to = $to;
    }

    public function getFrom(): DateTimeImmutable
    {
        return $this->from;
    }

    public function getTo(): DateTimeImmutable
    {
        return $this->to;
    }
}
