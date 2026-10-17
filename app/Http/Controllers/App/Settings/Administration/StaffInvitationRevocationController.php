<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Administration;

use App\Actions\RevokeStaffInvitation;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class StaffInvitationRevocationController extends Controller
{
    public function create(Request $request, int $staff): RedirectResponse
    {
        $staff = $request->user()->company->staff()->findOrFail($staff);

        try {
            new RevokeStaffInvitation(
                author: $request->user(),
                staff: $staff,
            )->execute();
        } catch (InvalidArgumentException) {
            return redirect()->route('settings.team.index')
                ->withErrors(['invitation' => __('Only an invitation waiting for an answer can be revoked.')]);
        }

        return redirect()->route('settings.team.index')
            ->with('status', __('The invitation is revoked.'))
            ->with('status_description', __('The link sent to :name no longer works.', ['name' => $staff->name]));
    }
}
