<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Actions\DismissWelcomeScreen;
use App\Http\Controllers\Controller;
use App\ViewModels\WelcomeViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WelcomeController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (! $request->user()->company->is_welcome_screen_pending) {
            return redirect()->route('home.index');
        }

        return view('app.welcome', [
            'viewModel' => new WelcomeViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        new DismissWelcomeScreen(
            user: $request->user(),
        )->execute();

        return redirect()->route('home.index');
    }
}
