<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Administration;

use App\Actions\DestroyUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamAccountController extends Controller
{
    public function destroy(Request $request, int $staff): RedirectResponse
    {
        $staff = $request->user()->company->staff()->with('user')->findOrFail($staff);

        if ($staff->user === null) {
            abort(404);
        }

        new DestroyUser(
            author: $request->user(),
            user: $staff->user,
        )->execute();

        return redirect()->route('settings.team.show', $staff->id)
            ->with('status', __('The account is removed.'))
            ->with('status_description', __(':name can no longer sign in, and can still be booked.', ['name' => $staff->name]));
    }
}
