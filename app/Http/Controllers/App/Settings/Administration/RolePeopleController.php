<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Administration;

use App\Actions\AssignRole;
use App\Actions\RemoveRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RolePeopleController extends Controller
{
    public function create(Request $request, int $role): RedirectResponse
    {
        $validated = $request->validate([
            'user' => ['required', 'integer'],
        ]);

        $user = new AssignRole(
            author: $request->user(),
            user: $request->user()->company->users()->findOrFail($validated['user']),
            role: $request->user()->company->roles()->findOrFail($role),
        )->execute();

        return redirect()->route('settings.roles.show', [$role, 'people'])
            ->with('status', __('The role is handed out.'))
            ->with('status_description', __(':email holds it from now on, in place of their previous role.', ['email' => $user->email]));
    }

    public function destroy(Request $request, int $role, int $user): RedirectResponse
    {
        $user = new RemoveRole(
            author: $request->user(),
            user: $request->user()->company->users()->findOrFail($user),
            role: $request->user()->company->roles()->findOrFail($role),
        )->execute();

        return redirect()->route('settings.roles.show', [$role, 'people'])
            ->with('status', __('The role is taken back.'))
            ->with('status_description', __(':email holds no role any more.', ['email' => $user->email]));
    }
}
