<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->header('lang')
            ?? $request->header('accept_lang')
            ?? $request->header('Accept-Language');

        if ($locale) {
            // Accept-Language can contain values like "ar,en;q=0.9".
            $locale = trim(explode(',', $locale)[0]);

            // Normalize locale values like en-US / en_US to en.
            if (str_contains($locale, '-')) {
                $locale = explode('-', $locale)[0];
            } elseif (str_contains($locale, '_')) {
                $locale = explode('_', $locale)[0];
            }

            $locale = strtolower($locale);

            if (in_array($locale, ['en', 'ar'], true)) {
                App::setLocale($locale);
            }
        }

        return $next($request);
    }
}
