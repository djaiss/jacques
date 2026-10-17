<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Onboarding;

use App\Http\Controllers\Controller;
use App\ViewModels\Onboarding\CompletionViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompletionController extends Controller
{
    public function index(Request $request): View
    {
        abort_if($request->user()->company->is_onboarding_pending, 404);

        return view('app.onboarding.completion', [
            'viewModel' => new CompletionViewModel(user: $request->user()),
        ]);
    }
}
