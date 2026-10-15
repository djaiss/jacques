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
            'staff' => ['required', 'integer'],
        ]);

        $staff = new AssignLocation(
            author: $request->user(),
            staff: $request->user()->company->staff()->findOrFail($validated['staff']),
            location: $request->user()->company->locations()->findOrFail($location),
        )->execute();

        return back()
            ->with('status', __('The person is assigned to the location.'))
            ->with('status_description', __(':name works there from now on.', ['name' => $staff->name]));
    }

    public function destroy(Request $request, int $location, int $staff): RedirectResponse
    {
        $staff = new RemoveLocation(
            author: $request->user(),
            staff: $request->user()->company->staff()->findOrFail($staff),
            location: $request->user()->company->locations()->findOrFail($location),
        )->execute();

        return back()
            ->with('status', __('The person is taken off the location.'))
            ->with('status_description', __(':name keeps every other location they work at.', ['name' => $staff->name]));
    }
}
