<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Administration;

use App\Actions\CreateRole;
use App\Actions\DestroyRole;
use App\Actions\UpdateRole;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Role;
use App\ViewModels\Settings\Administration\RolesViewModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize($request);

        return view('app.settings.administration.roles.index', [
            'viewModel' => new RolesViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function show(Request $request, int $role, ?string $tab = null): View
    {
        $this->authorize($request);

        return view('app.settings.administration.roles.show', [
            'viewModel' => new RolesViewModel(
                user: $request->user(),
                role: $this->roles($request)->findOrFail($role),
                onPeopleTab: $tab === 'people',
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        // Its own error bag: the dialog and the screen behind it both have a
        // field called `name`.
        $validated = $request->validateWithBag('createRole', [
            'name' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        $role = new CreateRole(
            author: $request->user(),
            company: $request->user()->company,
            name: $validated['name'],
            grants: [],
        )->execute();

        return redirect()->route('settings.roles.show', $role->id)
            ->with('status', __('The role is created.'))
            ->with('status_description', __('Nothing changes for anybody until you hand it out.'));
    }

    public function update(Request $request, int $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['nullable', 'array'],
            'permissions.*.granted' => ['nullable'],
            'permissions.*.scope' => ['nullable', 'string', Rule::enum(ScopeEnum::class)],
        ]);

        $grants = [];

        foreach ($validated['permissions'] ?? [] as $value => $submitted) {
            $permission = PermissionEnum::tryFrom((string) $value);

            if ($permission === null || ! isset($submitted['granted'])) {
                continue;
            }

            $scope = ScopeEnum::tryFrom($submitted['scope'] ?? '');

            $grants[] = [
                'permission' => $permission,
                'scope' => in_array($scope, $permission->scopes(), true) ? $scope : ScopeEnum::Company,
            ];
        }

        new UpdateRole(
            author: $request->user(),
            role: $this->roles($request)->findOrFail($role),
            name: $validated['name'],
            grants: $grants,
            description: $validated['description'] ?? null,
        )->execute();

        return redirect()->route('settings.roles.show', $role)
            ->with('status', __('The role is saved.'))
            ->with('status_description', __('Everybody who holds it is covered by it from now on.'));
    }

    public function destroy(Request $request, int $role): RedirectResponse
    {
        new DestroyRole(
            author: $request->user(),
            role: $this->roles($request)->findOrFail($role),
        )->execute();

        return redirect()->route('settings.roles.index')
            ->with('status', __('The role is deleted.'))
            ->with('status_description', __('What it granted is granted by it no longer.'));
    }

    private function authorize(Request $request): void
    {
        $request->user()
            ->permission(PermissionEnum::RoleManage)
            ->forCompany($request->user()->company)
            ->authorize();
    }

    /** @return HasMany<Role, Company> */
    private function roles(Request $request): HasMany
    {
        return $request->user()->company->roles();
    }
}
