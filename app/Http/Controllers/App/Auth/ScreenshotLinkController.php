<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Helpers\ScreenshotLink;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ScreenshotLinkController extends Controller
{
    public function show(Request $request): RedirectResponse
    {
        abort_unless(ScreenshotLink::isAvailable(), 404);
        abort_unless(ScreenshotLink::isDevelopmentHost($request->getHost()), 404);

        $user = User::query()
            ->where('email', mb_strtolower((string) $request->query('email')))
            ->firstOrFail();

        // The link only exists on a development instance, so it skips the two
        // factor challenge that a magic link never skips.
        Auth::guard('web')->login($user);

        $request->session()->regenerate();

        $destination = (string) $request->query('to', '/');

        // Anything but a path on this site would make the link an open redirect.
        if (! Str::startsWith($destination, '/') || Str::startsWith($destination, ['//', '/\\'])) {
            $destination = '/';
        }

        return redirect($destination);
    }
}
