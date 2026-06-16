<?php

declare(strict_types=1);

use HelpTeam\Controller\SubmitAdController;
use HelpTeam\Controller\ReverseGeocodeController;
use HelpTeam\Repository\AdRepository;
use HelpTeam\Service\MediaUploadService;

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

if ($path === '/api/geocode/reverse') {
    if ($method !== 'POST') {
        jsonResponse(['ok' => false, 'message' => 'Метод не поддерживается.'], 405);
    }

    $controller = new ReverseGeocodeController(env('YANDEX_GEOCODER_API_KEY', '') ?? '');
    $result = $controller->handle(file_get_contents('php://input') ?: '');

    jsonResponse($result['payload'], $result['status']);
}

$route = $routes[$path] ?? null;
$viewData = [];

if ($path === '/submit') {
    $controller = submitAdController($appRoot, __DIR__);

    if ($method === 'POST') {
        $result = $controller->submit($_POST, $_FILES);

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

function submitAdController(string $appRoot, string $publicPath): SubmitAdController
{
    $config = require $appRoot . '/config/ads.php';

    return new SubmitAdController(
        new AdRepository(),
        new MediaUploadService($publicPath),
        $config['categories'] ?? [],
        env('YANDEX_MAPS_API_KEY', '') ?? ''
    );
}

/**
 * @param array<string, mixed> $payload
 */
function jsonResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
