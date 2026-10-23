<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Services;

use App\Actions\ActivateService;
use App\Actions\DeactivateService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceArchiveController extends Controller
{
    public function create(Request $request, int $service): RedirectResponse
    {
        new DeactivateService(
            author: $request->user(),
            service: $request->user()->company->services()->findOrFail($service),
        )->execute();

        return redirect()->route('services.index')
            ->with('status', __('The service is archived.'))
            ->with('status_description', __('Nobody can book it. Its page keeps everything, and it can come back from there.'));
    }

    public function destroy(Request $request, int $service): RedirectResponse
    {
        new ActivateService(
            author: $request->user(),
            service: $request->user()->company->services()->findOrFail($service),
        )->execute();

        return redirect()->route('services.show', $service)
            ->with('status', __('The service is back in the catalog.'));
    }
}
