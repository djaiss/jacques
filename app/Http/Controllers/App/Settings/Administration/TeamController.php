<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Administration;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\ViewModels\Settings\Administration\TeamViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $request->user()
            ->permission(PermissionEnum::RoleManage)
            ->forCompany($request->user()->company)
            ->authorize();

        return view('app.settings.administration.team.index', [
            'viewModel' => new TeamViewModel(user: $request->user()),
        ]);
    }
}
