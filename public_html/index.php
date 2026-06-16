<?php

declare(strict_types=1);

$appBootstrap = dirname(__DIR__) . '/help-team-site/bootstrap/app.php';

if (!is_file($appBootstrap)) {
    $appBootstrap = dirname(__DIR__) . '/bootstrap/app.php';
}

require $appBootstrap;

$routes = [
    '/' => [
        'title' => 'РљР°С‚Р°Р»РѕРі РѕР±СЉСЏРІР»РµРЅРёР№',
        'view' => 'home',
    ],
    '/submit' => [
        'title' => 'РџРѕРґР°С‚СЊ РѕР±СЉСЏРІР»РµРЅРёРµ',
        'view' => 'submit',
    ],
    '/map' => [
        'title' => 'РљР°СЂС‚Р° РѕР±СЉСЏРІР»РµРЅРёР№',
        'view' => 'map',
    ],
    '/about' => [
        'title' => 'Рћ РЅР°СЃ',
        'view' => 'about',
    ],
];

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/') ?: '/';
$route = $routes[$path] ?? null;

if ($route === null) {
    http_response_code(404);

    $route = [
        'title' => 'РЎС‚СЂР°РЅРёС†Р° РЅРµ РЅР°Р№РґРµРЅР°',
        'view' => '404',
    ];
}

$title = $route['title'];
$view = $route['view'];

require dirname(__DIR__) . '/resources/views/layout.php';
