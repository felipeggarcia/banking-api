<?php

declare(strict_types=1);

namespace Banking;

final class Accounts
{
    /** @var array<string, int> */
    private array $balances = [];

    public function balanceOf(string $id): int
    {
        if (!isset($this->balances[$id])) {
            throw new AccountNotFound($id);
        }

        return $this->balances[$id];
    }
}
