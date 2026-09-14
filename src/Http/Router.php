<?php

declare(strict_types=1);

namespace Banking\Http;

use Banking\Domain\Accounts;
use Banking\Domain\AccountNotFound;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;

final class Router
{
    public function __construct(private readonly Accounts $accounts)
    {
    }

    public function handle(ServerRequestInterface $request): Response
    {
        try {
            return $this->route($request);
        } catch (AccountNotFound) {
            return new Response(404, [], '0');
        }
    }

    private function route(ServerRequestInterface $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        return match (true) {
            $method === 'POST' && $path === '/reset' => $this->reset(),
            $method === 'POST' && $path === '/event' => $this->event($request),
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

    private function event(ServerRequestInterface $request): Response
    {
        $body = json_decode((string) $request->getBody(), true);

        $type = $body['type'];
        $destination = $body['destination'] ?? '';
        $origin = $body['origin'] ?? '';
        $amount = $body['amount'];

        switch ($type) {
            case 'deposit':
                $this->accounts->deposit($destination, $amount);

                return $this->created(['destination' => $this->snapshot($destination)]);

            case 'withdraw':
                $this->accounts->withdraw($origin, $amount);

                return $this->created(['origin' => $this->snapshot($origin)]);

            case 'transfer':
                $this->accounts->transfer($origin, $destination, $amount);

                return $this->created([
                    'origin' => $this->snapshot($origin),
                    'destination' => $this->snapshot($destination),
                ]);
        }
    }

    /**
     * @return array{id: string, balance: int}
     */
    private function snapshot(string $id): array
    {
        return ['id' => $id, 'balance' => $this->accounts->balanceOf($id)];
    }

    /**
     * @param array<string, array{id: string, balance: int}> $payload
     */
    private function created(array $payload): Response
    {
        return new Response(201, [], (string) json_encode($payload));
    }

}
