<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Onboarding\Catalogue;

use App\Actions\UpdateServicePrices;
use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ServicePriceController extends Controller
{
    public function update(Request $request, Service $service): RedirectResponse
    {
        abort_unless($service->company_id === $request->user()->company_id, 404);

        $request->merge(['prices' => $this->amounts($request)]);

        $validated = $request->validateWithBag("service_{$service->id}", [
            'prices' => ['required', 'array'],
            'prices.*' => ['nullable', 'decimal:0,2', 'min:0', 'max:1000000'],
        ], [
            'prices.*' => __('Enter a price of 0 or more, with at most two decimals.'),
        ]);

        try {
            new UpdateServicePrices(
                author: $request->user(),
                service: $service,
                prices: array_map(fn (?string $amount): ?int => $amount === null ? null : (int) round((float) $amount * 100), $validated['prices']),
            )->execute();
        } catch (InvalidArgumentException) {
            return back()->withErrors(['service' => __('These prices could not be saved.')], "service_{$service->id}");
        }

        $request->session()->forget('onboarding.removal');

        return redirect()->route('onboarding.services.index')->with('catalogue_saved', true);
    }

    /**
     * The amounts as typed, with a decimal comma read as a decimal point and an
     * empty field read as no price.
     *
     * @return array<string, string|null>
     */
    private function amounts(Request $request): array
    {
        return array_map(
            fn (mixed $amount): ?string => blank($amount) ? null : str_replace(',', '.', trim((string) $amount)),
            (array) $request->input('prices', []),
        );
    }
}
