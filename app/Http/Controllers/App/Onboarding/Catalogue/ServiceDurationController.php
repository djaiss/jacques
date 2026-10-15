<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Onboarding\Catalogue;

use App\Actions\UpdateServiceDuration;
use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ServiceDurationController extends Controller
{
    public function update(Request $request, Service $service): RedirectResponse
    {
        abort_unless($service->company_id === $request->user()->company_id, 404);

        $validated = $request->validateWithBag("service_{$service->id}", [
            'duration' => ['required', 'integer', 'min:1', 'max:1440'],
        ], [
            'duration.*' => __('Enter a duration between 1 and 1440 minutes.'),
        ]);

        try {
            new UpdateServiceDuration(
                author: $request->user(),
                service: $service,
                duration: (int) $validated['duration'],
            )->execute();
        } catch (InvalidArgumentException) {
            return back()->withErrors(['service' => __('This service lasts as long as its steps.')], "service_{$service->id}");
        }

        $request->session()->forget('onboarding.removal');

        return redirect()->route('onboarding.services.index')->with('catalogue_saved', true);
    }
}
