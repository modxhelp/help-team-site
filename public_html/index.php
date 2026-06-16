<?php

declare(strict_types=1);

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

$route = $routes[$path] ?? null;

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