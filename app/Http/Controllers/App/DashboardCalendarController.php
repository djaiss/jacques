<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\ViewModels\Dashboard\DashboardViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardCalendarController extends Controller
{
    public function show(Request $request, string $date, string $month): View
    {
        $timezone = $request->user()->company->locations()->orderBy('id')->value('timezone') ?? config('app.timezone');
        $day = CarbonImmutable::createFromFormat('!Y-m-d', $date, $timezone);
        $firstOfMonth = CarbonImmutable::createFromFormat('!Y-m', $month, $timezone);

        if ($day === null || $day->format('Y-m-d') !== $date) {
            abort(404);
        }

        if ($firstOfMonth === null || $firstOfMonth->format('Y-m') !== $month) {
            abort(404);
        }

        return view('app.dashboard', [
            'viewModel' => new DashboardViewModel(
                date: $day,
                now: CarbonImmutable::now($timezone),
                month: $firstOfMonth,
            ),
        ]);
    }
}
