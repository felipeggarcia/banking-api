<?php

declare(strict_types=1);

namespace Banking\Domain;

use RuntimeException;

final class InsufficientFunds extends RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Account {$id} with insufficient funds.");
    }
}
