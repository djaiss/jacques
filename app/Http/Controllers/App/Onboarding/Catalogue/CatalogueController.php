<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Onboarding\Catalogue;

use App\Actions\DestroyCatalogue;
use App\Actions\RestoreDefaultCatalogue;
use App\Helpers\CatalogueSnapshot;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CatalogueController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        try {
            new RestoreDefaultCatalogue(
                author: $request->user(),
                company: $request->user()->company,
            )->execute();
        } catch (InvalidArgumentException) {
            return back()->with('error', __('The example catalog only comes back into an empty catalog.'));
        }

        $request->session()->forget('onboarding.removal');

        return redirect()->route('onboarding.services.index')->with('catalogue_saved', true);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $snapshot = CatalogueSnapshot::ofCompany($request->user()->company);

        new DestroyCatalogue(
            author: $request->user(),
            company: $request->user()->company,
        )->execute();

        $request->session()->put('onboarding.removal', [
            'message' => __('The catalog was emptied.'),
            'snapshot' => $snapshot,
        ]);

        return redirect()->route('onboarding.services.index');
    }
}
