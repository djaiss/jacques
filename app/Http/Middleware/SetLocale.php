<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// The session wins over the account, which wins over the browser.
class SetLocale
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->preferred($request);

        if (array_key_exists($locale, config('jacques.locales'))) {
            app()->setLocale($locale);
        }

        return $next($request);
    }

    private function preferred(Request $request): string
    {
        $session = $request->session()->get('locale');

        if ($session !== null) {
            return $session;
        }

        $user = $request->user();

        if ($user === null) {
            return config('app.locale');
        }

        return $user->locale ?? $user->company->locale ?? config('app.locale');
    }
}
