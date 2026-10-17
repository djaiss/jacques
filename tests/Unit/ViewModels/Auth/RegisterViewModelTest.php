<?php

declare(strict_types=1);

use App\ViewModels\Auth\RegisterViewModel;

it('gives a quote', function () {
    $quote = new RegisterViewModel()->quote();

    expect($quote)->toHaveKey('text');
    expect($quote)->toHaveKey('author');
    expect($quote)->toHaveKey('source');
});

it('gives the languages the interface speaks', function () {
    $locales = new RegisterViewModel()->locales();

    expect($locales)->toHaveCount(4);
    expect($locales[0])->toEqual([
        'label' => 'English',
        'region' => 'United Kingdom',
        'flag' => config('jacques.locales.en.flag'),
        'code' => 'en',
    ]);
});

it('gives the urls of the legal pages', function () {
    $viewModel = new RegisterViewModel;

    expect($viewModel->termsUrl())->toEqual(config('jacques.terms_url'));
    expect($viewModel->privacyUrl())->toEqual(config('jacques.privacy_url'));
});
