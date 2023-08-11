<?php

use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

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

$app->group('/api', function (RouteCollectorProxy $group): void {
    $group->get('/index/[{id}]', function (Request $request, Response $response, array $args): Response {
        global $pdo;

        $userId = $args['id'];

        $stmt = $pdo->prepare("select * from user_tb where user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return response_json($response, array('message' => $user));
    });

    $group->get('/users/{token}', function (Request $request, Response $response, array $args): Response {
        if (!empty($args['token'])) {
            if ($args['token'] == TOKEN) {
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
