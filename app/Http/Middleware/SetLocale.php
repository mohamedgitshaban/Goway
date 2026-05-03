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
        if ($request->hasHeader('Accept-Language')) {
            $locale = $request->header('Accept-Language');
            
            // Optionally, you can validate if the locale is supported e.g. in_array($locale, ['en', 'ar'])
            if (in_array($locale, ['en', 'ar'])) {
                App::setLocale($locale);
            }
        }

        return $next($request);
    }
}
