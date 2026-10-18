<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Administration;

use App\Actions\PrepareStaffInvitation;
use App\Actions\SendStaffInvitation;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StaffInvitationSendingController extends Controller
{
    public function create(Request $request, int $staff): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['nullable', 'integer'],
        ]);

        $staff = $request->user()->company->staff()->findOrFail($staff);
        $role = isset($validated['role_id'])
            ? $request->user()->company->roles()->findOrFail($validated['role_id'])
            : null;

        try {
            DB::transaction(function () use ($request, $staff, $role): void {
                if ($role !== null) {
                    new PrepareStaffInvitation(
                        author: $request->user(),
                        staff: $staff,
                        role: $role,
                    )->execute();
                }

                new SendStaffInvitation(
                    author: $request->user(),
                    staff: $staff->refresh(),
                )->execute();
            });
        } catch (InvalidArgumentException) {
            return redirect()->route('settings.team.show', $staff->id)
                ->withErrors(['panel' => __('The invitation of :name cannot be sent.', ['name' => $staff->name])]);
        }

        return redirect()->route('settings.team.show', $staff->id)
            ->with('status', __('The invitation is on its way.'))
            ->with('status_description', __(':name receives the link at :email.', ['name' => $staff->name, 'email' => $staff->email]));
    }
}
