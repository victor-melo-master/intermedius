<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        return null;
    }

    protected function unauthenticated($request, array $guards): void
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            abort(401, 'No autenticado.');
        }

        parent::unauthenticated($request, $guards);
    }
}
