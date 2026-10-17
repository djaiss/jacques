<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Onboarding;

use App\Actions\ApplyOpeningHoursToTeam;
use App\Actions\CompleteOnboardingStep;
use App\Actions\SyncLocationOpeningHours;
use App\Enums\OnboardingStepEnum;
use App\Helpers\WeeklyHours;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\ViewModels\Onboarding\AvailabilityViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class AvailabilityController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.onboarding.availability', [
            'viewModel' => new AvailabilityViewModel(
                user: $request->user(),
                step: OnboardingStepEnum::Availability,
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'array:0,1,2,3,4,5,6'],
            'days.*.open' => ['required', 'boolean'],
            'days.*.ranges' => ['required_if_accepted:days.*.open', 'array'],
            'days.*.ranges.*.start' => ['required', 'string', Rule::in(WeeklyHours::grid())],
            'days.*.ranges.*.end' => ['required', 'string', Rule::in(WeeklyHours::grid())],
            'use_opening_hours' => ['required', 'boolean'],
        ]);

        $author = $request->user();

        $headquarters = Location::query()
            ->where('company_id', $author->company_id)
            ->where('is_headquarters', true)
            ->firstOrFail();

        try {
            DB::transaction(function () use ($author, $validated, $headquarters): void {
                new SyncLocationOpeningHours(
                    author: $author,
                    location: $headquarters,
                    week: WeeklyHours::fromForm($validated['days']),
                )->execute();

                new ApplyOpeningHoursToTeam(
                    author: $author,
                    location: $headquarters,
                    overrideCustomHours: (bool) $validated['use_opening_hours'],
                )->execute();

                new CompleteOnboardingStep(
                    author: $author,
                    step: OnboardingStepEnum::Availability,
                )->execute();
            });
        } catch (InvalidArgumentException) {
            return back()->withErrors(['days' => __('Fix the opening hours marked in red to finish.')]);
        }

        return redirect()->route('dashboard.index');
    }
}
