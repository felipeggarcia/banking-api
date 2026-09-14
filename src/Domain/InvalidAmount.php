<?php

declare(strict_types=1);

namespace Banking\Domain;

use RuntimeException;

final class InvalidAmount extends RuntimeException
{
    public function __construct(int $value)
    {
       parent::__construct("Amount must be positive, got {$value}.");
    }
}
