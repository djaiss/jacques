<?php

declare(strict_types=1);

namespace App\ViewModels\Settings;

use App\Enums\PermissionEnum;
use App\Enums\TimeFormatEnum;
use App\Models\Employee;
use App\Models\User;

class SettingsViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly ?Employee $employee,
    ) {}

    public function companyName(): string
    {
        return $this->user->company->name;
    }

    public function name(): string
    {
        return $this->employee->name ?? $this->user->email;
    }

    public function employee(): ?Employee
    {
        return $this->employee;
    }

    public function role(): string
    {
        return collect([$this->employee?->custom_title, $this->user->roles->first()?->name])
            ->filter()
            ->implode(' · ');
    }

    /** @return array<int, array{title: string, description: string, value: string, url: string, hue: int, icon: string}> */
    public function accountRows(): array
    {
        return [
            [
                'title' => __('Profile'),
                'description' => __('Your name, avatar, work email and emergency contact.'),
                'value' => '',
                'url' => route('settings.profile.index'),
                'hue' => 30,
                'icon' => 'profile',
            ],
            [
                'title' => __('Security and access'),
                'description' => __('Your password, your devices and your API keys.'),
                'value' => $this->user->usesTwoFactorAuthentication() ? __('Two-factor on') : __('Two-factor off'),
                'url' => route('settings.security.index'),
                'hue' => 150,
                'icon' => 'security',
            ],
            [
                'title' => __('Preferences'),
                'description' => __('Language, dates, numbers and the clock.'),
                'value' => $this->preferencesValue(),
                'url' => route('settings.preferences.index'),
                'hue' => 310,
                'icon' => 'preferences',
            ],
            [
                'title' => __('Logs'),
                'description' => __('Everything you have done in :app, with dates.', ['app' => config('app.name')]),
                'value' => trans_choice(':count entry|:count entries', $this->user->logs()->count()),
                'url' => route('settings.logs.index'),
                'hue' => 200,
                'icon' => 'logs',
            ],
        ];
    }

    /** @return array<int, array{title: string, description: string, value: string, url: string, hue: int, icon: string}> */
    public function companyRows(): array
    {
        $rows = [];

        if ($this->canManageCompany()) {
            $rows[] = [
                'title' => __('Offices'),
                'description' => __('The offices your company works from, and who sits where.'),
                'value' => trans_choice(':count office|:count offices', $this->user->company->offices()->count()),
                'url' => route('settings.offices.index'),
                'hue' => 80,
                'icon' => 'offices',
            ];
        }

        if ($this->canManageRoles()) {
            $rows[] = [
                'title' => __('Roles and permissions'),
                'description' => __('Who can see and change what across the company.'),
                'value' => $this->rolesValue(),
                'url' => route('settings.roles.index'),
                'hue' => 20,
                'icon' => 'roles',
            ];
        }

        return $rows;
    }

    public function canManageCompany(): bool
    {
        return $this->user
            ->permission(PermissionEnum::CompanyManage)
            ->forCompany($this->user->company)
            ->allowed();
    }

    public function canManageRoles(): bool
    {
        return $this->user
            ->permission(PermissionEnum::RoleManage)
            ->forCompany($this->user->company)
            ->allowed();
    }

    private function preferencesValue(): string
    {
        $locale = app()->getLocale();

        if (! array_key_exists($locale, config('jacques.locales'))) {
            $locale = config('app.locale');
        }

        $timeFormat = $this->user->time_format ?? TimeFormatEnum::TwentyFourHour;

        return config('jacques.locales')[$locale]['label'].' · '.__($timeFormat->label());
    }

    private function rolesValue(): string
    {
        $roles = trans_choice(':count role|:count roles', $this->user->company->roles()->count());
        $people = trans_choice(':count person|:count people', $this->user->company->users()->count());

        return $roles.' · '.$people;
    }
}
