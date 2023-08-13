<?php

use App\Foundation\Database\Query;
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

$query = new Query();

$app->group('/api', function (RouteCollectorProxy $group): void {
    $group->get('/users/{token}', function (Request $request, Response $response, array $args): Response {
        global $query;

        if (!empty($args['token'])) {
            if ($args['token'] == TOKEN) {
                $userList = [];
                $users = $query->table('users')->orderBy('user_id', 'desc')->get();
                $rows = count($users);
                if ($rows > 0) {
                    foreach ($users as $user) {
                        $imageURL = '';
                        if (!empty($user['user_profile'])) {
                            $imageURL = image_url(__DIR__ . "/../upload/image/{$user['user_profile']}");
                        }

                        $userList[] = [
                            'user_id' => $user['user_id'],
                            'user_name' => $user['user_name'],
                            'user_pass' => $user['user_pass'],
                            'user_phone' => $user['user_phone'],
                            'user_email' => $user['user_email'],
                            'user_token' => $user['user_token'],
                            'user_profile' => $imageURL,
                            'user_status' => $user['user_status'],
                            'create_date_at' => $user['create_date_at'],
                            'create_time_at' => $user['create_time_at']
                        ];
                    }
                    return json($response, array('data' => $userList, 'rows' => $rows));
                }
                return json($response, array('data' => $userList, 'rows' => 0));
            }
            return json($response, array('message' => 'token does not match'), 500);
        }
        return json($response, array('message' => 'token not found'), 500);
    });

    $group->post('/createUser', function (Request $request, Response $response, array $args): Response {
        global $query;
        $body = $request->getParsedBody();

        if (!empty($body['token'])) {
            if ($body['token'] == TOKEN) {

                $fileName = '';
                $username = str($body['username']);
                $userEmail = str($body['email']);
                $rememberMe = str($body['rememberMe']);
                $file = !empty($_FILES['profileFile']) ? $_FILES['profileFile'] : [];

                if (!empty($file['name'])) {
                    $fileData = ['name' => str($file['name']), 'tmp_name' => str($file['tmp_name'])];
                    $fileName = file_uploaded(__DIR__ . '../../upload/image/', $fileData);
                }

                $password = rend_string();

                $query->table('users')->insert([
                    'user_name' => $username,
                    'user_pass' => $password,
                    'user_phone' => '',
                    'user_email' => $userEmail,
                    'user_token' => U_SYS_TOKEN,
                    'user_profile' => $fileName,
                    'user_status' => 'Y',
                    'create_date_at' => CREATE_DATE_AT,
                    'create_time_at' => CREATE_TIME_AT
                ]);

                return json($response, array('message' => 'create user success...', 'status' => true));
            }
            return json($response, array('message' => 'token does not match', 'status' => false), 500);
        }
        return json($response, array('message' => 'token not found'), 500);
    });

    $group->delete('/createUser/{id}', function (Request $request, Response $response, array $args): Response {
        return json($response, array('message' => 'token not found'), 500);
    });
});

$app->run();
