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
}
