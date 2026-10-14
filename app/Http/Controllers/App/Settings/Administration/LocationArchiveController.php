<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Administration;

use App\Actions\ArchiveLocation;
use App\Actions\RestoreLocation;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocationArchiveController extends Controller
{
    public function create(Request $request, int $location): RedirectResponse
    {
        new ArchiveLocation(
            author: $request->user(),
            location: $this->locations($request)->findOrFail($location),
        )->execute();

        return back()
            ->with('status', __('The location is archived.'))
            ->with('status_description', __('Nothing written about it is lost. It is on the archived list whenever you want it back.'));
    }

    public function destroy(Request $request, int $location): RedirectResponse
    {
        new RestoreLocation(
            author: $request->user(),
            location: $this->locations($request)->findOrFail($location),
        )->execute();

        return back()
            ->with('status', __('The location is open again.'))
            ->with('status_description', __('It comes back as an ordinary location. Promote it if it is the headquarters.'));
    }

    /** @return HasMany<Location, Company> */
    private function locations(Request $request): HasMany
    {
        return $request->user()->company->locations();
    }
}
