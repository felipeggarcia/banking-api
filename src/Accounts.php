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

    public function deposit(string $id, int $value): int
    {
        if (!isset($this->balances[$id])) {
            $this->balances[$id] = 0;
        }

        $this->balances[$id] += $value;

        return $this->balances[$id];
    }
}
