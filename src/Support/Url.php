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

        $configuredPath = Env::get('APP_BASE_PATH');
        if (is_string($configuredPath) && $configuredPath !== '') {
            $trimmedPath = trim(str_replace('\\', '/', $configuredPath));
            self::$basePath = $trimmedPath === '/' ? '' : '/' . trim($trimmedPath, '/');
            return self::$basePath;
        }

        $appRoot = realpath(__DIR__ . '/../..');
        $appRootNormalized = $appRoot ? str_replace('\\', '/', $appRoot) : '';

        // Derive the base from the running script: DOCUMENT_ROOT on shared hosts (e.g. InfinityFree)
        // often points to a different physical path than the files, which breaks the comparison below.
        $scriptFile = isset($_SERVER['SCRIPT_FILENAME']) ? realpath((string) $_SERVER['SCRIPT_FILENAME']) : false;
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']) : '';
        if ($appRootNormalized !== '' && $scriptFile && $scriptName !== '' && PHP_SAPI !== 'cli') {
            $scriptFile = str_replace('\\', '/', $scriptFile);
            if (strpos($scriptFile, $appRootNormalized . '/') === 0) {
                $relativeScript = substr($scriptFile, strlen($appRootNormalized));
                $suffixLength = strlen($relativeScript);
                if (strlen($scriptName) >= $suffixLength && strcasecmp(substr($scriptName, -$suffixLength), $relativeScript) === 0) {
                    $prefix = rtrim(substr($scriptName, 0, -$suffixLength), '/');
                    self::$basePath = $prefix === '' ? '' : '/' . trim($prefix, '/');
                    return self::$basePath;
                }
            }
        }

        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;

        if ($documentRoot && $appRoot) {
            $documentRoot = str_replace('\\', '/', $documentRoot);
            $appRoot = $appRootNormalized;

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

        $fragment = '';
        $fragmentAt = strpos($path, '#');
        if ($fragmentAt !== false) {
            $fragment = substr($path, $fragmentAt);
            $path = substr($path, 0, $fragmentAt);
        }

        $basePath = self::basePath();
        $url = ($basePath !== '' ? $basePath : '') . '/' . ltrim($path, '/');
        if (class_exists('App\\Support\\Auth') && session_status() === PHP_SESSION_ACTIVE) {
            $context = (string) ($_SESSION['_active_context'] ?? '');
            if ($context !== '' && preg_match('/^[a-f0-9]{64}$/', $context) && !preg_match('/(?:^|[?&])ctx=[a-f0-9]{64}(?:&|$)/', $url)) {
                $separator = strpos($url, '?') === false ? '?' : '&';
                $url .= $separator . 'ctx=' . rawurlencode($context);
            }
        }
        return $url . $fragment;
    }

    public static function redirect($path)
    {
        header('Location: ' . self::to($path));
        exit();
    }
}

