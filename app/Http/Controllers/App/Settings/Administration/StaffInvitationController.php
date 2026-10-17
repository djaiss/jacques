<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Administration;

use App\Actions\DestroyStaffInvitation;
use App\Actions\PrepareStaffInvitation;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class StaffInvitationController extends Controller
{
    public function create(Request $request, int $staff): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'integer'],
        ]);

        $staff = $request->user()->company->staff()->findOrFail($staff);

        try {
            new PrepareStaffInvitation(
                author: $request->user(),
                staff: $staff,
                role: $request->user()->company->roles()->findOrFail($validated['role_id']),
            )->execute();
        } catch (InvalidArgumentException) {
            return redirect()->route('settings.team.index')
                ->withErrors(['invitation' => __('An invitation cannot be prepared for :name.', ['name' => $staff->name])]);
        }

        return redirect()->route('settings.team.index')
            ->with('status', __('The invitation is ready.'))
            ->with('status_description', __('Send it to :name when you are ready.', ['name' => $staff->name]));
    }

    public function destroy(Request $request, int $staff): RedirectResponse
    {
        $staff = $request->user()->company->staff()->findOrFail($staff);

        new DestroyStaffInvitation(
            author: $request->user(),
            staff: $staff,
        )->execute();

        return redirect()->route('settings.team.index')
            ->with('status', __('The invitation is cancelled.'))
            ->with('status_description', __(':name stays on the team without an account.', ['name' => $staff->name]));
    }
}
