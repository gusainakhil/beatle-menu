<?php

// Front Controller - Single entry point

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/helpers.php';

use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

Session::start();

$request = new Request();
$router = new Router();

$routes = require __DIR__ . '/../config/routes.php';

foreach ($routes as $method => $methodRoutes) {
    foreach ($methodRoutes as $path => $handler) {
        $register = strtolower($method);
        if (method_exists($router, $register)) {
            $router->$register($path, $handler);
        }
    }
}

$router->resolve($request);
