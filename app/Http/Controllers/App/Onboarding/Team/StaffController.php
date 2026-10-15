<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Onboarding\Team;

use App\Actions\AssignLocation;
use App\Actions\CreateStaff;
use App\Actions\DestroyStaff;
use App\Actions\DestroyStaffInvitation;
use App\Actions\PrepareStaffInvitation;
use App\Actions\SyncStaffLocationServices;
use App\Actions\UpdateStaffInformation;
use App\Enums\OnboardingStepEnum;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use App\ViewModels\Onboarding\TeamViewModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;
use Illuminate\View\View;
use InvalidArgumentException;

class StaffController extends Controller
{
    public function new(Request $request): View
    {
        return view('app.onboarding.staff', [
            'viewModel' => new TeamViewModel(
                user: $request->user(),
                step: OnboardingStepEnum::Staff,
                isAddingStaff: true,
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $validator = $this->validator($request);

        /*
         * The panel opens without changing the address of the page, so going
         * back would close it and lose the errors.
         */
        if ($validator->fails()) {
            return redirect()->route('onboarding.staff.members.new')->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();
        $author = $request->user();

        $headquarters = Location::query()
            ->where('company_id', $author->company_id)
            ->where('is_headquarters', true)
            ->firstOrFail();

        try {
            DB::transaction(function () use ($author, $validated, $headquarters): void {
                $staff = new CreateStaff(
                    author: $author,
                    company: $author->company,
                    firstName: $validated['first_name'],
                    lastName: $validated['last_name'] ?? null,
                    displayName: $validated['display_name'] ?? null,
                    email: $validated['email'] ?? null,
                    phone: $validated['phone'] ?? null,
                    jobTitle: $validated['job_title'] ?? null,
                    bio: $validated['bio'] ?? null,
                    isBookable: (bool) ($validated['is_bookable'] ?? false),
                )->execute();

                new AssignLocation(author: $author, staff: $staff, location: $headquarters)->execute();

                new SyncStaffLocationServices(
                    author: $author,
                    staff: $staff,
                    location: $headquarters,
                    locationServiceIds: array_map(intval(...), $validated['services'] ?? []),
                )->execute();

                $this->updateInvitation($author, $staff, $validated);
            });
        } catch (InvalidArgumentException) {
            return redirect()->route('onboarding.staff.members.new')->withErrors(['email' => __('Another team member already uses this email address.')])->withInput();
        } catch (ModelNotFoundException) {
            abort(404);
        }

        return redirect()->route('onboarding.staff.index');
    }

    public function edit(Request $request, Staff $staff): View
    {
        abort_unless($staff->company_id === $request->user()->company_id, 404);

        return view('app.onboarding.staff', [
            'viewModel' => new TeamViewModel(
                user: $request->user(),
                step: OnboardingStepEnum::Staff,
                openStaff: $staff,
            ),
        ]);
    }

    public function update(Request $request, Staff $staff): RedirectResponse
    {
        abort_unless($staff->company_id === $request->user()->company_id, 404);

        $validator = $this->validator($request);

        if ($validator->fails()) {
            return redirect()->route('onboarding.staff.members.edit', $staff)->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();
        $author = $request->user();
        $isOwnRecord = $staff->user_id === $author->id;

        $headquarters = Location::query()
            ->where('company_id', $author->company_id)
            ->where('is_headquarters', true)
            ->firstOrFail();

        try {
            DB::transaction(function () use ($author, $staff, $validated, $headquarters, $isOwnRecord): void {
                new UpdateStaffInformation(
                    author: $author,
                    staff: $staff,
                    firstName: $validated['first_name'],
                    lastName: $validated['last_name'] ?? null,
                    displayName: $validated['display_name'] ?? null,
                    email: $isOwnRecord ? $staff->email : ($validated['email'] ?? null),
                    phone: $validated['phone'] ?? null,
                    jobTitle: $validated['job_title'] ?? null,
                    bio: $validated['bio'] ?? null,
                    isBookable: (bool) ($validated['is_bookable'] ?? false),
                )->execute();

                new AssignLocation(author: $author, staff: $staff, location: $headquarters)->execute();

                new SyncStaffLocationServices(
                    author: $author,
                    staff: $staff,
                    location: $headquarters,
                    locationServiceIds: array_map(intval(...), $validated['services'] ?? []),
                )->execute();

                if (! $isOwnRecord && $staff->user_id === null) {
                    $this->updateInvitation($author, $staff, $validated);
                }
            });
        } catch (InvalidArgumentException) {
            return redirect()->route('onboarding.staff.members.edit', $staff)->withErrors(['email' => __('Another team member already uses this email address.')])->withInput();
        } catch (ModelNotFoundException) {
            abort(404);
        }

        return redirect()->route('onboarding.staff.index');
    }

    public function destroy(Request $request, Staff $staff): RedirectResponse
    {
        abort_unless($staff->company_id === $request->user()->company_id, 404);
        abort_if($staff->user_id === $request->user()->id, 404);

        new DestroyStaff(author: $request->user(), staff: $staff)->execute();

        return redirect()->route('onboarding.staff.index');
    }

    private function validator(Request $request): ValidationValidator
    {
        return Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'is_bookable' => ['nullable', 'boolean'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer'],
            'invite' => ['nullable', 'boolean'],
            'role' => [Rule::requiredIf($request->boolean('invite') && $request->filled('email')), 'nullable', 'integer'],
        ], [
            'first_name.required' => __('A first name is needed.'),
        ]);
    }

    /**
     * Prepare the invitation when the box is ticked and there is an address to
     * send it to, and withdraw it otherwise.
     *
     * @param  array<string, mixed>  $validated
     */
    private function updateInvitation(User $author, Staff $staff, array $validated): void
    {
        if (! ($validated['invite'] ?? false) || $staff->email === null) {
            new DestroyStaffInvitation(author: $author, staff: $staff)->execute();

            return;
        }

        new PrepareStaffInvitation(
            author: $author,
            staff: $staff,
            role: Role::query()->findOrFail($validated['role']),
        )->execute();
    }
}
