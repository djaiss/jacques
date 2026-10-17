<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Invitation;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvitationSignOutController extends Controller
{
    public function create(Request $request, string $token): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('invitations.show', ['token' => $token]);
    }
}
