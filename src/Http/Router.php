<?php

declare(strict_types=1);

namespace Banking\Http;

use Banking\Domain\Accounts;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;

final class Router
{
    public function __construct(private readonly Accounts $accounts)
    {
    }

    public function handle(ServerRequestInterface $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        return match (true) {
            $method === 'POST' && $path === '/reset' => $this->reset(),
            $method === 'GET' && $path === '/balance' => $this->balance($request),
        };
    }

    private function reset(): Response
    {
        $this->accounts->reset();

        return new Response(200);
    }

    private function balance(ServerRequestInterface $request): Response
    {
        $id = (string) ($request->getQueryParams()['account_id'] ?? '');

        return new Response(200, [], (string) $this->accounts->balanceOf($id));
    }
}
