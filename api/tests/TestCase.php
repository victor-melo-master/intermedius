<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
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
