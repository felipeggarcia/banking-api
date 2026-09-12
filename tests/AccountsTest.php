<?php

declare(strict_types=1);

namespace Tests;

use Banking\AccountNotFound;
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
}
