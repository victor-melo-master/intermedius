<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SanitizeLogs
{
    public function handle(Request $request, Closure $next)
    {
        // Sanitizar inputs antes de que lleguen al log
        $request->merge($this->sanitize($request->all()));
        return $next($request);
    }

    private function sanitize(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'api_token', 'secret'];
        foreach ($data as $key => $value) {
            if (in_array($key, $sensitiveKeys)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            }
        }
        return $data;
    }
}
