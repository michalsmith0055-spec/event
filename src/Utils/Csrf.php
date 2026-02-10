<?php

declare(strict_types=1);

namespace App\Utils;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    public static function validate(?string $token): bool
    {
        return !empty($token) && hash_equals($_SESSION['_csrf_token'] ?? '', $token);
    }
}
