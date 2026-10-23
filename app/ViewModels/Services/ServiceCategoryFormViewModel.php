<?php

declare(strict_types=1);

namespace App\ViewModels\Services;

use App\Helpers\CatalogueText;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;

class ServiceCategoryFormViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly ?ServiceCategory $category = null,
    ) {}

    public function isNew(): bool
    {
        return $this->category === null;
    }

    public function title(): string
    {
        if ($this->category === null) {
            return __('New category');
        }

        return $this->category->localizedName(app()->getLocale()) ?: __('Untitled category');
    }

    /** @return array<string, string|null> */
    public function breadcrumb(): array
    {
        return [
            $this->user->company->name => route('dashboard.index'),
            __('Services') => route('services.index'),
            $this->title() => null,
        ];
    }

    public function action(): string
    {
        return $this->category === null
            ? route('serviceCategories.create')
            : route('serviceCategories.update', $this->category->id);
    }

    public function method(): string
    {
        return $this->isNew() ? 'post' : 'put';
    }

    public function saveLabel(): string
    {
        return $this->isNew() ? __('Add this category') : __('Save changes');
    }

    public function destroyUrl(): ?string
    {
        return $this->category === null ? null : route('serviceCategories.destroy', $this->category->id);
    }

    /** @return list<string> */
    public function languages(): array
    {
        return $this->user->company->languages;
    }

    public function firstLanguage(): string
    {
        return $this->user->company->languages[0];
    }

    public function languageLabel(string $language): string
    {
        return (string) config("jacques.content_languages.{$language}.label", $language);
    }

    /** @return array<string, string> */
    public function names(): array
    {
        $typed = (array) old('name', $this->category->name ?? []);
        $names = [];

        foreach ($this->languages() as $language) {
            $names[$language] = (string) ($typed[$language] ?? '');
        }

        return $names;
    }

    /** @return list<string> */
    public function serviceNames(): array
    {
        if ($this->category === null) {
            return [];
        }

        return $this->category->services()
            ->orderBy('position')
            ->get()
            ->map(fn (Service $service): string => CatalogueText::resolve($service->name ?? [], $this->languages(), app()->getLocale()) ?: __('Untitled service'))
            ->all();
    }

    public function removalWarning(): string
    {
        $names = $this->serviceNames();

        if ($names === []) {
            return __('The category is empty, so nothing else changes.');
        }

        return trans_choice(':count service stays in the catalog, without a category: :names.|:count services stay in the catalog, without a category: :names.', count($names), [
            'names' => implode(', ', $names),
        ]);
    }
}
