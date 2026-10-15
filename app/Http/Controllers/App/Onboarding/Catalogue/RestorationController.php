<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Onboarding\Catalogue;

use App\Actions\RestoreCatalogueSnapshot;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RestorationController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        $removal = $request->session()->pull('onboarding.removal');

        if ($removal === null) {
            return redirect()->route('onboarding.services.index')->with('error', __('There is nothing left to put back.'));
        }

        try {
            new RestoreCatalogueSnapshot(
                author: $request->user(),
                company: $request->user()->company,
                snapshot: $removal['snapshot'],
            )->execute();
        } catch (ModelNotFoundException) {
            return redirect()->route('onboarding.services.index')->with('error', __('There is nothing left to put back.'));
        }

        return redirect()->route('onboarding.services.index')->with('catalogue_saved', true);
    }
}
