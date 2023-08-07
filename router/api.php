<?php

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Routing\RouteCollectorProxy;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '../../src/dependency.php';

$app = AppFactory::create();

$app->addRoutingMiddleware();

$app->addErrorMiddleware(true, true, true);

$app->add(function (Request $request, RequestHandler $handler): Response {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', '*')
        ->withHeader('Access-Control-Allow-Methods', '*');
});

enum API: string
{
    const Token = '79f5b6d5e8c3280e5db1d5bda60c46232b2c858bf3dd060b0cc065a83f394b27';
}

$app->group('/api', function (RouteCollectorProxy $group): void {
    $group->get('/users/[{token}]', function (Request $request, Response $response, array $args): Response {
        if (!empty($args['token'])) {
            if ($args['token'] == API::Token) {
                $users = db_select("select * from user_tb");
                $rows = count($users);
                return response_json($response, array('data' => $users, 'rows' => $rows));
            }
            return response_json($response, array('message' => 'token does not match'), 500);
        }
        return response_json($response, array('message' => 'token not found'), 500);
    });
});

$app->run();
