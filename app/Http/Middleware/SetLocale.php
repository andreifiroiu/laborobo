<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get language from authenticated user, cookie, or config default
        $locale = $this->getLocale($request);

        // Validate locale is supported
        if (! in_array($locale, config('app.available_locales', ['en']))) {
            $locale = config('app.fallback_locale', 'en');
        }

        // Set application locale
        App::setLocale($locale);

        return $next($request);
    }

    /**
     * Get the locale for the request.
     */
    protected function getLocale(Request $request): string
    {
        // Priority: User preference > Cookie > Config default
        if ($request->user()?->language) {
            return $request->user()->language;
        }

        if ($request->hasCookie('language')) {
            return $request->cookie('language');
        }

        return config('app.locale', 'en');
    }
}
