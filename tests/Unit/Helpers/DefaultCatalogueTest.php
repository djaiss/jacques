<?php

declare(strict_types=1);

use App\Helpers\DefaultCatalogue;

it('lists six categories and seventeen services', function () {
    $categories = DefaultCatalogue::categories();

    expect($categories)->toHaveCount(6);
    expect(array_merge(...array_column($categories, 'services')))->toHaveCount(17);
});

it('gives every service at least one step that requires a team member', function () {
    foreach (DefaultCatalogue::categories() as $category) {
        foreach ($category['services'] as $service) {
            expect(array_column($service['steps'], 'requires_staff'))->toContain(true);
        }
    }
});

it('writes a text in every interface locale', function () {
    $texts = DefaultCatalogue::texts(fn (string $locale): string => __('Processing', locale: $locale));

    expect(array_keys($texts))->toEqualCanonicalizing(['en', 'fr', 'de', 'es']);
    expect($texts['en'])->toEqual('Processing');
});
