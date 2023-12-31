<?php

/**
 * @author PUG <pugpaprika21@gmail.com>
 * @edit 10-10-2566
 */

use Illuminate\Database\Capsule\Manager as Capsule;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_cache_limiter(false);
session_start();

date_default_timezone_set('Asia/Bangkok');

define('APP_NAME', '');
define('WRITE_LOG', false);
define('CREATE_DATE_AT', now('d'));
define('CREATE_TIME_AT', now('t'));
define('CREATE_DT_AT', now());
define('U_SYS_TOKEN', token_generator(rend_string() . CREATE_TIME_AT));
define('U_IP', getenv('HTTP_X_FORWARDED_FOR') ? getenv('HTTP_X_FORWARDED_FOR') : getenv("REMOTE_ADDR"));
define('APP_URL', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __DIR__);

$database = require(__DIR__ . '../../config/database.php');

$capsule = new Capsule;
$capsule->addConnection($database['connection']['eloquent']);
$capsule->bootEloquent();
$capsule->setAsGlobal();


/* API */

const TOKEN = '79f5b6d5e8c3280e5db1d5bda60c46232b2c858bf3dd060b0cc065a83f394b27';

$pdo = call_pdo();
