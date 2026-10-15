<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Onboarding;

use App\Actions\CompleteOnboardingStep;
use App\Enums\OnboardingStepEnum;
use App\Http\Controllers\Controller;
use App\ViewModels\Onboarding\StepViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.onboarding.appointments', [
            'viewModel' => new StepViewModel(
                user: $request->user(),
                step: OnboardingStepEnum::Appointments,
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        new CompleteOnboardingStep(
            author: $request->user(),
            step: OnboardingStepEnum::Appointments,
        )->execute();

        return redirect()->route('home.index');
    }
}
