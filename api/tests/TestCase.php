<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private static function testingEnv(): array
    {
        return [
            'APP_ENV' => 'testing',
            'CACHE_STORE' => 'array',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => env('DB_HOST', 'db'),
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'intermedius_casa_cambio_test',
            'DB_USERNAME' => 'laravel_user',
            'DB_PASSWORD' => 'secret',
            'MAIL_MAILER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'SESSION_DRIVER' => 'array',
        ];
    }

    public function createApplication()
    {
        foreach (self::testingEnv() as $name => $value) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }

        $prevHandler = set_error_handler(function (int $severity, string $message, string $file, int $line) use (&$prevHandler): bool {
            if (($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED)) {
                return true;
            }
            if ($prevHandler) {
                return $prevHandler($severity, $message, $file, $line);
            }
            return false;
        });

        $app = require Application::inferBasePath().'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        restore_error_handler();

        return $app;
    }
}
