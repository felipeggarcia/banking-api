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


}
