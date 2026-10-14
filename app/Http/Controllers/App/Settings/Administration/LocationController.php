<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Administration;

use App\Actions\CreateLocation;
use App\Actions\UpdateLocation;
use App\Enums\LocationScopeEnum;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Location;
use App\ViewModels\Settings\Administration\LocationsViewModel;
use DateTimeZone;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(Request $request, ?string $scope = null): View
    {
        $this->authorize($request);

        return view('app.settings.administration.locations.index', [
            'viewModel' => new LocationsViewModel(
                user: $request->user(),
                scope: LocationScopeEnum::fromSegment($scope),
                search: trim((string) $request->query('q', '')),
                sort: $request->query('sort') === 'place' ? 'place' : 'name',
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        // Its own error bag: the dialog and the screen behind it both have a
        // field called `name`.
        $validated = $request->validateWithBag('createLocation', [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2', 'alpha'],
        ]);

        new CreateLocation(
            author: $request->user(),
            company: $request->user()->company,
            name: $validated['name'],
            country: $validated['country'] ?? null,
            city: $validated['city'] ?? null,
        )->execute();

        return redirect()->route('settings.locations.index')
            ->with('status', __('The location is added.'))
            ->with('status_description', __('Give it a time zone and an address whenever you have them.'));
    }

    public function update(Request $request, int $location): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2', 'alpha'],
            'region' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'timezone' => ['nullable', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            'is_headquarters' => ['nullable'],
        ]);

        new UpdateLocation(
            author: $request->user(),
            location: $this->locations($request)->findOrFail($location),
            name: $validated['name'],
            country: $validated['country'] ?? null,
            region: $validated['region'] ?? null,
            city: $validated['city'] ?? null,
            postalCode: $validated['postal_code'] ?? null,
            addressLine1: $validated['address_line_1'] ?? null,
            addressLine2: $validated['address_line_2'] ?? null,
            phone: $validated['phone'] ?? null,
            email: $validated['email'] ?? null,
            timezone: $validated['timezone'] ?? null,
            isHeadquarters: isset($validated['is_headquarters']),
        )->execute();

        return back()
            ->with('status', __('The location is saved.'))
            ->with('status_description', __('Everybody who works there reads the new details from now on.'));
    }

    private function authorize(Request $request): void
    {
        $request->user()
            ->permission(PermissionEnum::CompanyManage)
            ->forCompany($request->user()->company)
            ->authorize();
    }

    /** @return HasMany<Location, Company> */
    private function locations(Request $request): HasMany
    {
        return $request->user()->company->locations();
    }
}
