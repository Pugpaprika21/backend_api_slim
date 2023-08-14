<?php

use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use App\Foundation\Database\Query;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '../../src/dependency.php';

$app = AppFactory::create();

$app->addRoutingMiddleware();

$app->addErrorMiddleware(true, true, true);

$app->options('/{routes:.+}', function (Request $request, Response $response, array $args): Response {
    return $response;
});

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
                $users = $query->table('users')
                    ->select('user_id', 'user_name', 'user_email', 'user_status', 'user_profile')
                    ->where('user_profile', '!=', '')
                    ->orderBy('user_id', 'desc')
                    ->get();

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
                            'user_email' => $user['user_email'],
                            'user_profile' => $imageURL,
                            'user_status' => $user['user_status']
                        ];
                    }
                    return json($response, ['data' => $userList, 'rows' => $rows]);
                }
                return json($response, ['data' => $userList, 'rows' => 0]);
            }
            return json($response, ['message' => 'token does not match'], 500);
        }
        return json($response, ['message' => 'token not found'], 500);
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

                return json($response, ['message' => 'create user success...', 'status' => true]);
            }
            return json($response, ['message' => 'token does not match', 'status' => false], 500);
        }
        return json($response, ['message' => 'token not found'], 500);
    });

    $group->get('/editUser/{id}', function (Request $request, Response $response, array $args): Response {
        global $query;
        $param = $request->getQueryParams();
        $userId = str($args['id']);

        if (!empty($param['token'])) {
            if ($param['token'] == TOKEN) {
                $userDetail = $query->table('users')
                    ->select('user_id', 'user_name', 'user_email', 'user_status', 'user_profile')
                    ->where('user_id', '=', $userId)
                    ->get(false);

                $imageURL = '';
                if (!empty($userDetail['user_profile'])) {
                    $imageURL = image_url(__DIR__ . "/../upload/image/{$userDetail['user_profile']}");
                }

                $userData = [
                    'user_id' => $userDetail['user_id'],
                    'user_name' => $userDetail['user_name'],
                    'user_email' => $userDetail['user_email'],
                    'user_status' => $userDetail['user_status'],
                    'user_profile' => $userDetail['user_profile'],
                    'user_image_path' => $imageURL,
                ];

                return json($response, ['data' => $userData, 'status' => true]);
            }
            return json($response, ['message' => 'token does not match', 'status' => false], 500);
        }
        return json($response, ['message' => 'token not found'], 500);
    });

    $group->put('/updateUser/{id}', function (Request $request, Response $response, array $args): Response {

        $bodys = array($request->getQueryParams(), $args, $_FILES, $_REQUEST);

        return json($response, ['data' => $bodys, 'status' => true]);
    });

    $group->delete('/deleteUser/{id}', function (Request $request, Response $response, array $args): Response {
        global $query;
        $param = $request->getQueryParams();

        if (!empty($param['token'])) {
            if ($param['token'] == TOKEN) {
                $userId = str($args['id']);

                $user = $query->excute("select user_id, user_profile from users where user_id = '{$userId}'", false);

                if (!empty($user['user_profile'])) {
                    unlink(__DIR__ . "../../upload/image/{$user['user_profile']}");
                }

                $deleteUser = $query->table('users')->where('user_id', '=', $user['user_id'])->delete();

                if ($deleteUser) {
                    return json($response, ['message' => 'delete user success...', 'status' => true]);
                }
                return json($response, ['message' => 'can`t delete user', 'status' => false], 500);
            }

            return json($response, ['message' => 'token does not match', 'status' => false], 500);
        }

        return json($response, ['message' => 'token not found'], 500);
    });
});

$app->run();
