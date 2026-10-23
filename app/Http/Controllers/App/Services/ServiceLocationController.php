<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Services;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\ViewModels\Services\ServicesViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceLocationController extends Controller
{
    public function index(Request $request, int $location): View
    {
        $request->user()->permission(PermissionEnum::ServiceView)->anywhere()->authorize();

        return view('app.services.index', [
            'viewModel' => new ServicesViewModel(
                user: $request->user(),
                location: $request->user()->company->locations()->whereNull('archived_at')->findOrFail($location),
                search: trim((string) $request->query('q', '')),
            ),
        ]);
    }
}
