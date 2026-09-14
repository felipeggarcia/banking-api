<?php

declare(strict_types=1);

namespace Tests\Http;

use Banking\Domain\AccountNotFound;
use Banking\Domain\Accounts;
use Banking\Http\Router;
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
}
