<?php

namespace App\Support;

class Locale
{
    public static function fromPath(string $path): array
    {
        $path = '/'.ltrim($path, '/');

        if ($path === '/nl' || $path === '/nl/') {
            return ['nl', '/'];
        }

        if (str_starts_with($path, '/nl/')) {
            return ['nl', substr($path, 3)];
        }

        return ['en', $path === '/' ? '/' : rtrim($path, '/').'/'];
    }

    public static function path(string $path, string $locale): string
    {
        if ($locale === 'en') {
            return $path;
        }

        return '/nl'.($path === '/' ? '/' : $path);
    }
}
