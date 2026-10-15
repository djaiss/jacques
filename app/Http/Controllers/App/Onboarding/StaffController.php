<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Onboarding;

use App\Actions\CompleteOnboardingStep;
use App\Enums\OnboardingStepEnum;
use App\Http\Controllers\Controller;
use App\ViewModels\Onboarding\TeamViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.onboarding.staff', [
            'viewModel' => new TeamViewModel(
                user: $request->user(),
                step: OnboardingStepEnum::Staff,
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        new CompleteOnboardingStep(
            author: $request->user(),
            step: OnboardingStepEnum::Staff,
        )->execute();

        return redirect()->route('onboarding.appointments.index');
    }
}
