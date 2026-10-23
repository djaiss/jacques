<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Clients;

use App\Enums\ClientSegmentEnum;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\ViewModels\Clients\ClientsViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientSegmentController extends Controller
{
    public function show(Request $request, ClientSegmentEnum $segment): View
    {
        $request->user()->permission(PermissionEnum::ClientView)->anywhere()->authorize();

        $viewModel = new ClientsViewModel(
            user: $request->user(),
            segment: $segment,
            location: $request->filled('location')
                ? $request->user()->company->locations()->whereNull('archived_at')->findOrFail($request->integer('location'))
                : null,
            search: trim((string) $request->query('q', '')),
            page: max(1, $request->integer('page', 1)),
        );

        abort_unless($viewModel->hasPage(), 404);

        return view('app.clients.index', [
            'viewModel' => $viewModel,
        ]);
    }
}
