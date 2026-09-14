<?php

declare(strict_types=1);

namespace Tests;

use Banking\Domain\AccountNotFound;
use Banking\Domain\InsufficientFunds;
use Banking\Domain\InvalidAmount;
use Banking\Domain\Accounts;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;  


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

    public function test_transfer_moves_money_between_existing_accounts (): void
    {
        $accounts = new Accounts();

        $accounts->deposit('100', 100);
        $accounts->deposit('300', 50);

        $accounts->transfer('100', '300', 20);

        $this->assertSame(70, $accounts->balanceOf('300'));
        $this->assertSame(80, $accounts->balanceOf('100'));
    }

    public function test_transfer_creates_non_existing_destination (): void
    {
        $accounts = new Accounts();

        $accounts->deposit('100', 100);

        $accounts->transfer('100', '300', 20);

        $this->assertSame(80, $accounts->balanceOf('100'));
        $this->assertSame(20, $accounts->balanceOf('300'));
    }

    public function test_transfer_from_non_existing_origin_is_rejected (): void
    {
        $accounts = new Accounts();

        $accounts->deposit('300', 50);

        $this->expectException(AccountNotFound::class);

        $accounts->transfer('100', '300', 20);

    }
    public function test_transfer_from_non_existing_origin_leaves_destination_untouched (): void
    {
        $accounts = new Accounts();

        $accounts->deposit('300', 50);

        try {
            $accounts->transfer('100', '300', 20);
            $this->fail('Expected AccountNotFound.');
        } catch (AccountNotFound) {}
        
        $this->assertSame(50, $accounts->balanceOf('300'));
    }

    public function test_transfer_with_insufficient_funds_is_rejected (): void
    {
        $accounts = new Accounts();

        $accounts->deposit('100', 10);
        $accounts->deposit('300', 50);

        $this->expectException(InsufficientFunds::class);

        $accounts->transfer('100', '300', 20);
    }

    public function test_transfer_with_insufficient_funds_leaves_both_accounts_untouched (): void
    {
        $accounts = new Accounts();

        $accounts->deposit('100', 10);
        $accounts->deposit('300', 50);

        try {
            $accounts->transfer('100', '300', 20);
            $this->fail('Expected InsufficientFunds.');
        } catch (InsufficientFunds) {}

        $this->assertSame(50, $accounts->balanceOf('300'));
        $this->assertSame(10, $accounts->balanceOf('100'));
    }

    public function test_transfer_with_insufficient_funds_does_not_create_destination (): void
    {
        $accounts = new Accounts();

        $accounts->deposit('100', 10);

        try {
            $accounts->transfer('100', '300', 20);
            $this->fail('Expected InsufficientFunds.');
        } catch (InsufficientFunds) {}

        $this->expectException(AccountNotFound::class);

        $accounts->balanceOf('300');
    }

    public function test_reset_clears_all_accounts (): void
    {
        $accounts = new Accounts();

        $accounts->deposit('100', 10);
        $accounts->deposit('300', 50);
        
        $accounts->reset();
        
        try {
            $accounts->balanceOf('100');
            $this->fail('Expected AccountNotFound.');
        } catch (AccountNotFound) {
            $this->addToAssertionCount(1);
        }

        try {
            $accounts->balanceOf('300');
            $this->fail('Expected AccountNotFound.');
        } catch (AccountNotFound) {
            $this->addToAssertionCount(1);
        }

    }

    public static function nonPositiveAmounts(): array
    {
        return [
            'negative' => [-10],
            'zero'     => [0],
        ];
    }

    #[DataProvider('nonPositiveAmounts')]
    public function test_deposit_rejects_non_positive_amount(int $amount): void
    {
        $accounts = new Accounts();

        $this->expectException(InvalidAmount::class);
        $accounts->deposit('100', $amount);
    }

    public function test_transfer_of_entire_balance_is_allowed(): void
    {
        $accounts = new Accounts();

        $accounts->deposit('100', 15);

        $accounts->transfer('100', '300', 15);

        $this->assertSame(0, $accounts->balanceOf('100'));
        $this->assertSame(15, $accounts->balanceOf('300'));
    }

    #[DataProvider('nonPositiveAmounts')]
    public function test_withdraw_rejects_non_positive_amount(int $amount): void
    {
        $accounts = new Accounts();

        $accounts->deposit('100', 50);

        $this->expectException(InvalidAmount::class);

        $accounts->withdraw('100', $amount);
    }

    #[DataProvider('nonPositiveAmounts')]
    public function test_transfer_rejects_non_positive_amount(int $amount): void
    {
        $accounts = new Accounts();

        $accounts->deposit('100', 50);

        $this->expectException(InvalidAmount::class);

        $accounts->transfer('100', '300', $amount);
    }

}

    