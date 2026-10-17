<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Invitation;

use App\Actions\AcceptStaffInvitation;
use App\Http\Controllers\Controller;
use App\Models\StaffInvitation;
use App\ViewModels\Invitation\InvitationViewModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use InvalidArgumentException;

class InvitationController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $invitation = StaffInvitation::query()
            ->with(['staff.company', 'role'])
            ->where('token_hash', hash('sha256', $token))
            ->first();

        $viewModel = new InvitationViewModel(
            token: $token,
            invitation: $invitation,
            viewer: $request->user(),
        );

        if ($viewModel->variant() === InvitationViewModel::SIGN_IN) {
            redirect()->setIntendedUrl(route('invitations.show', ['token' => $token]));
        }

        return view('app.invitations.show', [
            'viewModel' => $viewModel,
        ]);
    }

    public function create(Request $request, string $token): RedirectResponse
    {
        $user = $request->user();
        $password = null;

        if ($user === null) {
            $validated = $request->validate([
                'password' => ['required', 'string', 'max:255', Password::min(8)],
            ]);

            $password = $validated['password'];
        }

        try {
            $user = new AcceptStaffInvitation(
                token: $token,
                user: $user,
                password: $password,
            )->execute();
        } catch (InvalidArgumentException|ModelNotFoundException) {
            return redirect()->route('invitations.show', ['token' => $token]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard.index')
            ->with('welcome', $user->company->name);
    }
}
