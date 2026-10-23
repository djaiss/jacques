<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\ViewModels\Dashboard\DashboardViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $timezone = $request->user()->company->locations()->orderBy('id')->value('timezone') ?? config('app.timezone');
        $now = CarbonImmutable::now($timezone);

        return view('app.dashboard', [
            'viewModel' => new DashboardViewModel(
                date: $now->startOfDay(),
                now: $now,
            ),
        ]);
    }

    public function show(Request $request, string $date): View
    {
        $timezone = $request->user()->company->locations()->orderBy('id')->value('timezone') ?? config('app.timezone');
        $day = CarbonImmutable::createFromFormat('!Y-m-d', $date, $timezone);

        if ($day === null || $day->format('Y-m-d') !== $date) {
            abort(404);
        }

        return view('app.dashboard', [
            'viewModel' => new DashboardViewModel(
                date: $day,
                now: CarbonImmutable::now($timezone),
            ),
        ]);
    }
}
