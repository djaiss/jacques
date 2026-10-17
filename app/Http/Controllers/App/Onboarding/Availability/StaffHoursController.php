<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Onboarding\Availability;

use App\Actions\SyncStaffWorkingHours;
use App\Enums\OnboardingStepEnum;
use App\Helpers\WeeklyHours;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Staff;
use App\Models\User;
use App\ViewModels\Onboarding\AvailabilityViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class StaffHoursController extends Controller
{
    public function edit(Request $request, Staff $staff): View
    {
        $this->headquartersOf($request->user(), $staff);

        return view('app.onboarding.availability', [
            'viewModel' => new AvailabilityViewModel(
                user: $request->user(),
                step: OnboardingStepEnum::Availability,
                openStaff: $staff,
            ),
        ]);
    }

    public function update(Request $request, Staff $staff): RedirectResponse
    {
        $headquarters = $this->headquartersOf($request->user(), $staff);

        $validated = $request->validate([
            'days' => ['required', 'array:0,1,2,3,4,5,6'],
            'days.*.open' => ['required', 'boolean'],
            'days.*.ranges' => ['required_if_accepted:days.*.open', 'array'],
            'days.*.ranges.*.start' => ['required', 'string', Rule::in(WeeklyHours::grid())],
            'days.*.ranges.*.end' => ['required', 'string', Rule::in(WeeklyHours::grid())],
        ]);

        try {
            new SyncStaffWorkingHours(
                author: $request->user(),
                staff: $staff,
                location: $headquarters,
                week: WeeklyHours::fromForm($validated['days']),
                followsOpeningHours: false,
            )->execute();
        } catch (InvalidArgumentException) {
            return redirect()->route('onboarding.availability.staff.edit', $staff)->withErrors(['days' => __('Fix the hours marked in red to save.')]);
        }

        return redirect()->route('onboarding.availability.index');
    }

    /**
     * The headquarters, where the staff member has to be bookable for their
     * hours to be set from this step.
     */
    private function headquartersOf(User $author, Staff $staff): Location
    {
        abort_unless($staff->company_id === $author->company_id, 404);
        abort_unless($staff->is_active && $staff->is_bookable, 404);

        $headquarters = Location::query()
            ->where('company_id', $author->company_id)
            ->where('is_headquarters', true)
            ->firstOrFail();

        abort_unless($staff->activeLocations()->whereKey($headquarters->id)->exists(), 404);

        return $headquarters;
    }
}
