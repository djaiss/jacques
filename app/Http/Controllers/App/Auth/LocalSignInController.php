<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Actions\AttemptSignIn;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocalSignInController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        abort_unless(app()->environment('local'), 404);

        new AttemptSignIn(
            email: (string) config('jacques.seed_email'),
            password: (string) config('jacques.seed_password'),
            ip: $request->ip(),
        )->execute();

        $request->session()->regenerate();

        return redirect()->route('settings.profile.index');
    }
}
