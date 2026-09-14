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
        try{
            $id = (string) ($request->getQueryParams()['account_id'] ?? '');   
             return new Response(200, [], (string) $this->accounts->balanceOf($id));
        } catch (AccountNotFound) {
            return new Response(404, [], '0');
        }
    }

        private function event(ServerRequestInterface $request): Response
    {
        $body = json_decode((string) $request->getBody(), true);

        $type = $body['type'];
        $destination = $body['destination'] ?? '';
        $origin = $body['origin'] ?? '';
        $amount = $body['amount'];

        switch ($type){
            case 'deposit' :
                $this->accounts->deposit($destination,$amount);
                $balance = $this->accounts->balanceOf($destination);
                $response['destination'] = ['id'=> $destination, 'balance'=>$balance];
                return  new Response(201, [],  json_encode($response));

            case 'withdraw' :
                $this->accounts->withdraw($origin,$amount);
                $balance = $this->accounts->balanceOf($origin);
                $response['origin'] = ['id'=> $origin, 'balance'=>$balance];
                return  new Response(201, [],  json_encode($response));
        }

    }
}
