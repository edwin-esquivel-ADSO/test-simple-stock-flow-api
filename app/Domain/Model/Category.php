<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Exception\BusinessRuleViolation;

final class Category
{
    private string $id;
    private string $name;

    public function __construct(string $id, string $name)
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw new BusinessRuleViolation('El nombre de la categoría no puede estar vacío.');
        }

        $this->id = $id;
        $this->name = $trimmed;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
