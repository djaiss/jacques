<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->company->onboarding_step->routeName());
    }
}
