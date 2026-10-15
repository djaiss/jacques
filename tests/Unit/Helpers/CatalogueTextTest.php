<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\CatalogueText;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CatalogueTextTest extends TestCase
{
    #[Test]
    public function it_reads_the_text_in_the_language_of_the_interface(): void
    {
        $texts = ['en' => 'Women\'s haircut', 'fr' => 'Coupe femme'];

        $this->assertEquals('Coupe femme', CatalogueText::resolve(texts: $texts, languages: ['en', 'fr'], locale: 'fr_FR'));
        $this->assertEquals('Women\'s haircut', CatalogueText::resolve(texts: $texts, languages: ['en', 'fr'], locale: 'en'));
    }

    #[Test]
    public function it_falls_back_to_the_first_language_of_the_salon(): void
    {
        $texts = ['fr' => 'Coupe femme', 'en' => 'Women\'s haircut'];

        $this->assertEquals('Coupe femme', CatalogueText::resolve(texts: $texts, languages: ['fr', 'en'], locale: 'de_DE'));
    }

    #[Test]
    public function it_ignores_the_interface_language_when_the_salon_does_not_use_it(): void
    {
        $texts = ['en' => 'Women\'s haircut', 'es' => 'Corte de mujer'];

        $this->assertEquals('Women\'s haircut', CatalogueText::resolve(texts: $texts, languages: ['en'], locale: 'es_ES'));
    }

    #[Test]
    public function it_falls_back_to_the_first_language_holding_a_text(): void
    {
        $texts = ['en' => '', 'fr' => 'Coupe femme'];

        $this->assertEquals('Coupe femme', CatalogueText::resolve(texts: $texts, languages: ['en', 'fr', 'es'], locale: 'es_ES'));
    }

    #[Test]
    public function it_reads_nothing_when_no_language_of_the_salon_holds_a_text(): void
    {
        $this->assertEquals('', CatalogueText::resolve(texts: ['es' => 'Corte de mujer'], languages: ['en'], locale: 'en'));
        $this->assertEquals('', CatalogueText::resolve(texts: [], languages: ['en'], locale: 'en'));
    }

    #[Test]
    public function it_lists_the_languages_of_the_salon_missing_a_text(): void
    {
        $texts = ['en' => 'Women\'s haircut', 'fr' => '', 'de' => 'Damenhaarschnitt'];

        $this->assertEquals(['fr', 'es'], CatalogueText::missingLanguages(texts: $texts, languages: ['en', 'fr', 'es']));
        $this->assertEquals([], CatalogueText::missingLanguages(texts: $texts, languages: ['en']));
    }
}
