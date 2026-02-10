<?php

declare(strict_types=1);

use App\Controllers\DashboardController;
use App\Services\EventValidator;
use App\Services\ExcelParser;
use App\Services\FacebookService;
use App\Services\GoogleSheetsRepository;
use App\Utils\Logger;

require_once __DIR__ . '/../config/bootstrap.php';

$controller = new DashboardController(
    new ExcelParser(),
    new EventValidator(),
    new FacebookService(),
    new GoogleSheetsRepository(),
    new Logger()
);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

if ($path === '/' && $method === 'GET') {
    $controller->index();
} elseif ($path === '/upload' && $method === 'POST') {
    $controller->upload();
} elseif ($path === '/facebook-login' && $method === 'GET') {
    $controller->facebookLogin();
} elseif ($path === '/facebook-callback' && $method === 'GET') {
    $controller->facebookCallback();
} elseif ($path === '/select-page' && $method === 'POST') {
    $controller->selectPage();
} elseif ($path === '/submit-events' && $method === 'POST') {
    $controller->submitEvents();
} else {
    http_response_code(404);
    echo 'Not Found';
}
