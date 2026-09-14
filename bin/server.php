<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Banking\Domain\Accounts;
use Banking\Http\Router;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;

$port = (int) ($_SERVER['PORT'] ?? 8080);

$router = new Router(new Accounts());

$http = new HttpServer(
    static fn (ServerRequestInterface $request): Response => $router->handle($request)
);

$http->listen(new SocketServer('0.0.0.0:' . $port));

echo "Listening on http://0.0.0.0:{$port}\n";
