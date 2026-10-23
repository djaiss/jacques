<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Services;

use App\Actions\RestoreDefaultCatalogue;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ServiceCatalogueController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        try {
            new RestoreDefaultCatalogue(
                author: $request->user(),
                company: $request->user()->company,
            )->execute();
        } catch (InvalidArgumentException) {
            return redirect()->route('services.index')
                ->with('status', __('The ready-made catalog only goes into an empty catalog.'));
        }

        return redirect()->route('services.index')
            ->with('status', __('The ready-made catalog is added.'))
            ->with('status_description', __('Keep what you offer, change the prices, and remove the rest.'));
    }
}
