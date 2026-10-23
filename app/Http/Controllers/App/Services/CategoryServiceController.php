<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Services;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\ViewModels\Services\ServiceFormViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryServiceController extends Controller
{
    public function new(Request $request, int $category): View
    {
        $request->user()
            ->permission(PermissionEnum::ServiceManage)
            ->forCompany($request->user()->company)
            ->authorize();

        return view('app.services.new', [
            'viewModel' => new ServiceFormViewModel(
                user: $request->user(),
                category: $request->user()->company->serviceCategories()->findOrFail($category),
            ),
        ]);
    }
}
