<?php

// Suppress PHP 8.5+ deprecations from vendor code (PDO::MYSQL_ATTR_* constants)
set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    if (str_contains($file, 'vendor/')) {
        return true;
    }
    return false;
});

require_once __DIR__ . '/../vendor/autoload.php';

restore_error_handler();
