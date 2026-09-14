<?php

declare(strict_types=1);

namespace Tests\Http;

use Banking\Domain\AccountNotFound;
use Banking\Domain\Accounts;
use Banking\Http\Router;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use React\Http\Message\ServerRequest;

final class RouterTest extends TestCase
{
    public function test_reset_clears_all_accounts(): void
    {
        $accounts = new Accounts();
        $accounts->deposit('100', 10);

        $router = new Router($accounts);

        $response = $router->handle(new ServerRequest('POST', '/reset'));

        $this->assertSame(200, $response->getStatusCode());

        $this->expectException(AccountNotFound::class);

        $accounts->balanceOf('100');
    }

    public function test_balance_returns_the_balance_of_an_existing_account(): void
    {
        $accounts = new Accounts();
        $accounts->deposit('100', 20);

        $router = new Router($accounts);

        $response = $router->handle(new ServerRequest('GET', '/balance?account_id=100'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('20', (string) $response->getBody());
    }

    public function test_balance_of_non_existing_account_responds_404(): void
    {
        $router = new Router(new Accounts());

        $response = $router->handle(new ServerRequest('GET', '/balance?account_id=1234'));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('0', (string) $response->getBody());
    }

    public function test_deposit_event_creates_account_and_responds_201(): void
    {
        $accounts = new Accounts();
        $router = new Router($accounts);

        $response = $router->handle(new ServerRequest('POST', '/event', [], json_encode([
            'type' => 'deposit',
            'destination' => '100',
            'amount' => 10,
        ])));

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('{"destination":{"id":"100","balance":10}}', (string) $response->getBody());
        $this->assertSame(10, $accounts->balanceOf('100'));

    }

    public function test_withdraw_event_reduces_balance_and_responds_201(): void
    {
        $accounts = new Accounts();
        $accounts->deposit('100', 100);

        $router = new Router($accounts);

        $response = $router->handle(new ServerRequest('POST', '/event', [], json_encode([
            'type' => 'withdraw',
            'origin' => '100',
            'amount' => 10,
        ])));

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('{"origin":{"id":"100","balance":90}}', (string) $response->getBody());
        $this->assertSame(90, $accounts->balanceOf('100'));

    }

    public function test_transfer_event_moves_money_and_responds_201(): void
    {
        $accounts = new Accounts();
        $accounts->deposit('100', 80);
        $accounts->deposit('300', 20);

        $router = new Router($accounts);

        $response = $router->handle(new ServerRequest('POST', '/event', [], json_encode([
            'type' => 'transfer',
            'origin' => '100',
            'destination' => '300',
            'amount' => 10,
        ])));

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('{"origin":{"id":"100","balance":70},"destination":{"id":"300","balance":30}}', (string) $response->getBody());

        $this->assertSame(70, $accounts->balanceOf('100'));
        $this->assertSame(30, $accounts->balanceOf('300'));

    }

    
    public static function eventsRequiringExistingOrigin(): array
    {
        return [
            'withdraw' => [['type' => 'withdraw', 'origin' => '200', 'amount' => 10]],
            'transfer' => [['type' => 'transfer', 'origin' => '200', 'destination' => '300', 'amount' => 10]],
        ];
    }

    #[DataProvider('eventsRequiringExistingOrigin')]
    public function test_event_with_unknown_account_responds_404(array $payload): void
    {
        $accounts = new Accounts();

        $router = new Router($accounts);

        $response = $router->handle(new ServerRequest('POST', '/event', [], json_encode($payload)));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('0', (string) $response->getBody());

    }
    public static function eventsExceedingBalance(): array
    {
        return [
            'withdraw' => [['type' => 'withdraw', 'origin' => '100', 'amount' => 30]],
            'transfer' => [['type' => 'transfer', 'origin' => '100', 'destination' => '300', 'amount' => 30]],
        ];
    }

    #[DataProvider('eventsExceedingBalance')]
    public function test_event_with_insufficient_funds_responds_422(array $payload): void
    {
        $accounts = new Accounts();

        $router = new Router($accounts);

        $accounts->deposit('100', 10);
        $accounts->deposit('300', 50);
        $response = $router->handle(new ServerRequest('POST', '/event', [], json_encode($payload)));

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('0', (string) $response->getBody());

    }
}
