<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\OnboardingStepEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OnboardingStepGate
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next, ?string $step = null): Response
    {
        $user = $request->user();
        $company = $user->company;

        if (! $company->is_onboarding_pending) {
            return redirect()->route('home.index');
        }

        if (! $user->isOwner()) {
            return redirect()->route('home.index');
        }

        if ($step === null) {
            return $next($request);
        }

        $requested = OnboardingStepEnum::from((int) $step);

        if ($requested->value > $company->onboarding_step->value) {
            return redirect()->route($company->onboarding_step->routeName());
        }

        return $next($request);
    }
}
