<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Administration;

use App\Actions\SendStaffInvitation;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class StaffInvitationSendingController extends Controller
{
    public function create(Request $request, int $staff): RedirectResponse
    {
        $staff = $request->user()->company->staff()->findOrFail($staff);

        try {
            new SendStaffInvitation(
                author: $request->user(),
                staff: $staff,
            )->execute();
        } catch (InvalidArgumentException) {
            return redirect()->route('settings.team.index')
                ->withErrors(['invitation' => __('The invitation of :name cannot be sent.', ['name' => $staff->name])]);
        }

        return redirect()->route('settings.team.index')
            ->with('status', __('The invitation is on its way.'))
            ->with('status_description', __(':name receives the link at :email.', ['name' => $staff->name, 'email' => $staff->email]));
    }
}
