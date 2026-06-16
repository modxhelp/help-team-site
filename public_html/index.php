<?php

declare(strict_types=1);

use HelpTeam\Controller\SubmitAdController;
use HelpTeam\Repository\AdRepository;

$appRoot = dirname(__DIR__) . '/help-team-site';

if (!is_dir($appRoot)) {
    $appRoot = dirname(__DIR__);
}

require $appRoot . '/bootstrap/app.php';

$routes = [
    '/' => [
        'title' => 'Каталог объявлений',
        'view' => 'home',
    ],
    '/submit' => [
        'title' => 'Подать объявление',
        'view' => 'submit',
    ],
    '/map' => [
        'title' => 'Карта объявлений',
        'view' => 'map',
    ],
    '/about' => [
        'title' => 'О нас',
        'view' => 'about',
    ],
];

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/') ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

$route = $routes[$path] ?? null;
$viewData = [];

if ($path === '/submit') {
    $controller = submitAdController($appRoot);

    if ($method === 'POST') {
        $result = $controller->submit($_POST);

        if (isset($result['redirect'])) {
            header('Location: ' . $result['redirect'], true, 303);
            exit;
        }

        $viewData = $result;
    } else {
        $viewData = $controller->show();
    }
} elseif ($method === 'POST') {
    http_response_code(405);

    $route = [
        'title' => 'Метод не поддерживается',
        'view' => '404',
    ];
}

if ($route === null) {
    http_response_code(404);

    $route = [
        'title' => 'Страница не найдена',
        'view' => '404',
    ];
}

$title = $route['title'];
$view = $route['view'];

require $appRoot . '/resources/views/layout.php';

function submitAdController(string $appRoot): SubmitAdController
{
    $config = require $appRoot . '/config/ads.php';

    return new SubmitAdController(
        new AdRepository(),
        $config['categories'] ?? []
    );
}
