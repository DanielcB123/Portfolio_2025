<?php

namespace App\Support;

class AuthRedirects
{
    public static function resolve(?string $redirect, string $default): string
    {
        $redirect ??= $default;

        $allowedPaths = [
            route('dashboard', absolute: false),
            route('incident.dashboard', absolute: false),
        ];

        $path = parse_url($redirect, PHP_URL_PATH) ?? $redirect;

        if (! in_array($path, $allowedPaths, true)) {
            return $default;
        }

        return $redirect;
    }
}
