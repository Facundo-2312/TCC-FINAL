<?php

namespace App\Support;

// Centralized path/URL resolution so routes stay portable across install locations.
class Url
{
    private static $basePath = null;

    public static function basePath()
    {
        if (self::$basePath !== null) {
            return self::$basePath;
        }

        $configuredPath = getenv('APP_BASE_PATH');
        if (is_string($configuredPath) && $configuredPath !== '') {
            $trimmedPath = trim(str_replace('\\', '/', $configuredPath));
            self::$basePath = $trimmedPath === '/' ? '' : '/' . trim($trimmedPath, '/');
            return self::$basePath;
        }

        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;
        $appRoot = realpath(__DIR__ . '/../..');

        if ($documentRoot && $appRoot) {
            $documentRoot = str_replace('\\', '/', $documentRoot);
            $appRoot = str_replace('\\', '/', $appRoot);

            if (strpos($appRoot, $documentRoot) === 0) {
                $relativePath = trim(substr($appRoot, strlen($documentRoot)), '/');
                self::$basePath = $relativePath === '' ? '' : '/' . $relativePath;
                return self::$basePath;
            }
        }

        self::$basePath = '';
        return self::$basePath;
    }

    public static function to($path = '')
    {
        $path = (string) $path;

        if ($path === '') {
            return self::basePath() !== '' ? self::basePath() : '/';
        }

        if (preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $path) === 1) {
            return $path;
        }

        if ($path[0] === '/') {
            return $path;
        }

        $basePath = self::basePath();
        return ($basePath !== '' ? $basePath : '') . '/' . ltrim($path, '/');
    }

    public static function redirect($path)
    {
        header('Location: ' . self::to($path));
        exit();
    }
}
