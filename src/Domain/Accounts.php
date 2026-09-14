<?php

declare(strict_types=1);

namespace Banking\Domain;

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

    public function deposit(string $id, int $value): void
    {
        $this->guardPositive($value);

        if (!isset($this->balances[$id])) {
            $this->balances[$id] = 0;
        }

        $this->balances[$id] += $value;
    }

    public function withdraw(string $id, int $value): void
    { 
        $this->guardPositive($value);

        $balance = $this->balanceOf($id);

        if (($balance-$value)<0){
            throw new InsufficientFunds($id);
        }

        $this->balances[$id] = $balance - $value;
    }

    public function transfer(string $idOrigin, string $idDestination, int $value): void
    { 
        $this->guardPositive($value);

        $originBalance = $this->balanceOf($idOrigin);

        $destinationBalance = $this->balances[$idDestination] ?? 0;

        if (($originBalance - $value)<0){
            throw new InsufficientFunds($idOrigin);
        }

        $this->balances[$idOrigin] = $originBalance - $value;
        $this->balances[$idDestination] = $destinationBalance + $value;
    }

    public function reset (): void
    {
        $this->balances = [];
    }

    private function guardPositive(int $value): void
    {
        if ($value <= 0) {
            throw new InvalidAmount($value);
        }
    }

}
