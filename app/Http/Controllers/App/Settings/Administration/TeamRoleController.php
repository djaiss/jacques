<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Administration;

use App\Actions\AssignRole;
use App\Actions\PrepareStaffInvitation;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamRoleController extends Controller
{
    public function update(Request $request, int $staff): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'integer'],
        ]);

        $staff = $request->user()->company->staff()->with(['user', 'invitation'])->findOrFail($staff);
        $role = $request->user()->company->roles()->findOrFail($validated['role_id']);

        if ($staff->user === null && $staff->invitation === null) {
            abort(404);
        }

        if ($staff->user === null) {
            new PrepareStaffInvitation(author: $request->user(), staff: $staff, role: $role)->execute();
        }

        if ($staff->user !== null) {
            new AssignRole(author: $request->user(), user: $staff->user, role: $role)->execute();
        }

        return redirect()->route('settings.team.index')
            ->with('status', __('The access of :name is saved.', ['name' => $staff->name]));
    }
}
