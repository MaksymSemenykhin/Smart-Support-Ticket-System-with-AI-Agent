<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    private array $allowed = ['en', 'ru'];

    public function handle(Request $request, Closure $next)
    {
        $header = (string) $request->header('Accept-Language', '');
        $locale = strtolower(substr($header, 0, 2));

        if (! in_array($locale, $this->allowed, true)) {
            $locale = config('app.locale', 'en');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
