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
        $this->accounts->reset();

        return new Response(200);
    }
}
