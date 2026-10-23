<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Enums\PermissionEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

class TopBarLayout extends Component
{
    public function __construct(
        public ?string $title = null,
    ) {}

    public function render(): View
    {
        $user = Auth::user();

        return view('layouts.top-bar', [
            'companyName' => $user->company->name,
            'name' => $user->staff->name ?? $user->email,
            'staff' => $user->staff,
            'servicesUrl' => $user->permission(PermissionEnum::ServiceView)->anywhere()->allowed()
                ? route('services.index')
                : null,
            'teamUrl' => $user->permission(PermissionEnum::RoleManage)->forCompany($user->company)->allowed()
                ? route('settings.team.index')
                : null,
        ]);
    }
}
