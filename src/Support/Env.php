<?php

namespace App\Support;

// Loads KEY=VALUE pairs from a .env file into getenv()/$_ENV without overriding real OS env vars.
// No external dependency (no Composer) so this stays usable in a plain XAMPP deployment.
class Env
{
    private static $loaded = false;

    public static function load($path)
    {
        if (self::$loaded) {
            return;
        }

        self::$loaded = true;

        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }

            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim(trim($value), "\"'");

            if ($key === '' || getenv($key) !== false) {
                continue;
            }

            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}
