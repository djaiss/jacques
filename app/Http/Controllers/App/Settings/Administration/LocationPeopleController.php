<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Administration;

use App\Actions\AssignLocation;
use App\Actions\RemoveLocation;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocationPeopleController extends Controller
{
    public function create(Request $request, int $location): RedirectResponse
    {
        $validated = $request->validate([
            'user' => ['required', 'integer'],
        ]);

        $user = new AssignLocation(
            author: $request->user(),
            user: $request->user()->company->users()->findOrFail($validated['user']),
            location: $request->user()->company->locations()->findOrFail($location),
        )->execute();

        return back()
            ->with('status', __('The person is assigned to the location.'))
            ->with('status_description', __(':email works there from now on.', ['email' => $user->email]));
    }

    public function destroy(Request $request, int $location, int $user): RedirectResponse
    {
        $user = new RemoveLocation(
            author: $request->user(),
            user: $request->user()->company->users()->findOrFail($user),
            location: $request->user()->company->locations()->findOrFail($location),
        )->execute();

        return back()
            ->with('status', __('The person is taken off the location.'))
            ->with('status_description', __(':email keeps every other location they work at.', ['email' => $user->email]));
    }
}
