<?php

declare(strict_types=1);

use App\Helpers\CatalogueText;

it('reads the text in the language of the interface', function () {
    $texts = ['en' => 'Women\'s haircut', 'fr' => 'Coupe femme'];

    expect(CatalogueText::resolve(texts: $texts, languages: ['en', 'fr'], locale: 'fr_FR'))->toEqual('Coupe femme');
    expect(CatalogueText::resolve(texts: $texts, languages: ['en', 'fr'], locale: 'en'))->toEqual('Women\'s haircut');
});

it('falls back to the first language of the salon', function () {
    $texts = ['fr' => 'Coupe femme', 'en' => 'Women\'s haircut'];

    expect(CatalogueText::resolve(texts: $texts, languages: ['fr', 'en'], locale: 'de_DE'))->toEqual('Coupe femme');
});

it('ignores the interface language when the salon does not use it', function () {
    $texts = ['en' => 'Women\'s haircut', 'es' => 'Corte de mujer'];

    expect(CatalogueText::resolve(texts: $texts, languages: ['en'], locale: 'es_ES'))->toEqual('Women\'s haircut');
});

it('falls back to the first language holding a text', function () {
    $texts = ['en' => '', 'fr' => 'Coupe femme'];

    expect(CatalogueText::resolve(texts: $texts, languages: ['en', 'fr', 'es'], locale: 'es_ES'))->toEqual('Coupe femme');
});

it('reads nothing when no language of the salon holds a text', function () {
    expect(CatalogueText::resolve(texts: ['es' => 'Corte de mujer'], languages: ['en'], locale: 'en'))->toEqual('');
    expect(CatalogueText::resolve(texts: [], languages: ['en'], locale: 'en'))->toEqual('');
});

it('lists the languages of the salon missing a text', function () {
    $texts = ['en' => 'Women\'s haircut', 'fr' => '', 'de' => 'Damenhaarschnitt'];

    expect(CatalogueText::missingLanguages(texts: $texts, languages: ['en', 'fr', 'es']))->toEqual(['fr', 'es']);
    expect(CatalogueText::missingLanguages(texts: $texts, languages: ['en']))->toEqual([]);
});
