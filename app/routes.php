<?php

declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Controllers\AuthController;

return function (App $app) {

    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('MiniChat API funcionando');
        return $response;
    });

    // ✅ Auth endpoints
    $app->post('/register', [AuthController::class, 'register']);
    $app->post('/login', [AuthController::class, 'login']);
};
