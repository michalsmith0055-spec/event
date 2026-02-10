<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);
$autoloadCandidates = [
    $basePath . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
];

$autoloadPath = null;
foreach ($autoloadCandidates as $candidate) {
    if (is_file($candidate)) {
        $autoloadPath = $candidate;
        break;
    }
}

if ($autoloadPath === null) {
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');

    $safeBasePath = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');
    echo <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Setup Required</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; color: #222; }
        code { background: #f3f3f3; padding: 0.15rem 0.35rem; border-radius: 4px; }
        .box { max-width: 760px; border: 1px solid #ddd; padding: 1rem 1.25rem; border-radius: 8px; }
    </style>
</head>
<body>
<div class="box">
    <h2>Application setup is incomplete</h2>
    <p><strong>Missing file:</strong> <code>vendor/autoload.php</code></p>
    <p>Run Composer in your project root to install dependencies:</p>
    <pre><code>cd {$safeBasePath}
composer install</code></pre>
    <p>After installation, refresh this page.</p>
</div>
</body>
</html>
HTML;
    exit;
}

require_once $autoloadPath;

if (file_exists($basePath . '/.env') && class_exists(\Dotenv\Dotenv::class)) {
    $dotenv = \Dotenv\Dotenv::createImmutable($basePath);
    $dotenv->safeLoad();
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function env(string $key, ?string $default = null): ?string
{
    return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
}
