<?php

declare(strict_types=1);

use App\ViewModels\Onboarding\CatalogueLanguages;

it('counts the texts written in each language', function () {
    $languages = new CatalogueLanguages(
        languages: ['en', 'fr'],
        texts: [['en' => 'Cuts', 'fr' => 'Coupes'], ['en' => 'Haircut'], ['en' => 'Beard trim', 'fr' => '']],
    );

    $cards = $languages->cards();

    expect($cards[0]['isDefault'])->toBeTrue();
    expect($cards[0]['isComplete'])->toBeTrue();
    expect($cards[0]['progress'])->toEqual('3 of 3 fields translated');
    expect($cards[0]['status'])->toEqual('All translated');
    expect($cards[1]['isComplete'])->toBeFalse();
    expect($cards[1]['percent'])->toEqual(33);
    expect($cards[1]['status'])->toEqual('2 missing');
    expect($languages->summary())->toEqual('1 of 2 languages still need translations');
});

it('has nothing to translate in an empty catalogue', function () {
    $languages = new CatalogueLanguages(languages: ['en', 'fr'], texts: []);

    expect($languages->cards()[1]['status'])->toEqual('Nothing to translate');
    expect($languages->cards()[1]['hasNothingMissing'])->toBeTrue();
    expect($languages->cards()[1]['isComplete'])->toBeFalse();
    expect($languages->summary())->toEqual('2 languages');
});

it('says when every language is complete', function () {
    $languages = new CatalogueLanguages(languages: ['en'], texts: [['en' => 'Cuts']]);

    expect($languages->summary())->toEqual('Every language is complete');
});
