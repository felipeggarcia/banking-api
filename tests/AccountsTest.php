<?php

declare(strict_types=1);

namespace Tests;

use Banking\AccountNotFound;
use Banking\InsufficientFunds;
use Banking\Accounts;
use PHPUnit\Framework\TestCase;

final class AccountsTest extends TestCase
{
    public function test_balance_of_non_existing_account_is_rejected(): void
    {
        $accounts = new Accounts();

        $this->expectException(AccountNotFound::class);

        $accounts->balanceOf('1234');
    }

    public function test_deposit_creates_account_with_initial_balance(): void
    {
        $accounts = new Accounts();

        $accounts->deposit('100', 10);

        $this->assertSame(10, $accounts->balanceOf('100'));
    }

    public function test_deposit_increases_balance_of_existing_account(): void
    {
        $accounts = new Accounts();

        $accounts->deposit('100', 15);

        $accounts->deposit('100', 10);

        $this->assertSame(25, $accounts->balanceOf('100'));
    }

    public function test_withdraw_from_non_existing_account_is_rejected(): void
    {
        $accounts = new Accounts();

        $this->expectException(AccountNotFound::class);

        $accounts->withdraw('1234',20);
    }

    public function test_withdraw_reduces_balance_of_existing_account(): void
    {
        $accounts = new Accounts();

        $accounts->deposit('100', 100);

        $accounts->withdraw('100', 20);

        $this->assertSame(80, $accounts->balanceOf('100'));
    }

    public function test_withdraw_with_insufficient_funds_is_rejected(): void
    {
        $accounts = new Accounts();

        $accounts->deposit('100', 20);

        $this->expectException(InsufficientFunds::class);

        $accounts->withdraw('100', 100);
    }
    
    public function test_withdraw_with_insufficient_funds_leaves_balance_unchanged (): void
    {
        $accounts = new Accounts();

        $accounts->deposit('100', 20);

        try {
            $accounts->withdraw('100', 100);
            $this->fail('Expected InsufficientFunds.');
        } catch (InsufficientFunds) {}

        $this->assertSame(20, $accounts->balanceOf('100'));
    }

}

    