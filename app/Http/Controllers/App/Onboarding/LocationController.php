<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Onboarding;

use App\Actions\SetUpSalon;
use App\Http\Controllers\Controller;
use App\ViewModels\Onboarding\LocationViewModel;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.onboarding.location', [
            'viewModel' => new LocationViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'],
            'country' => ['required', 'string', 'size:2', 'alpha'],
        ], [
            'name.required' => __('The salon needs a name.'),
        ]);

        $country = mb_strtoupper($validated['country']);

        $request->validate([
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $country))],
        ]);

        new SetUpSalon(
            author: $request->user(),
            name: $validated['name'],
            country: $country,
            timezone: $request->input('timezone'),
            region: $validated['region'] ?? null,
            city: $validated['city'] ?? null,
            postalCode: $validated['postal_code'] ?? null,
            addressLine1: $validated['address_line_1'] ?? null,
            phone: $validated['phone'] ?? null,
        )->execute();

        return redirect()->route('onboarding.location.index');
    }
}
