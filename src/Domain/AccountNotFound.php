<?php

declare(strict_types=1);

namespace Banking\Domain;

use RuntimeException;

final class AccountNotFound extends RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Account {$id} does not exist.");
    }
}
