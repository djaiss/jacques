<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Onboarding;

use App\ViewModels\Onboarding\CatalogueLanguages;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CatalogueLanguagesTest extends TestCase
{
    #[Test]
    public function it_counts_the_texts_written_in_each_language(): void
    {
        $languages = new CatalogueLanguages(
            languages: ['en', 'fr'],
            texts: [['en' => 'Cuts', 'fr' => 'Coupes'], ['en' => 'Haircut'], ['en' => 'Beard trim', 'fr' => '']],
        );

        $cards = $languages->cards();

        $this->assertTrue($cards[0]['isDefault']);
        $this->assertTrue($cards[0]['isComplete']);
        $this->assertEquals('3 of 3 fields translated', $cards[0]['progress']);
        $this->assertEquals('All translated', $cards[0]['status']);
        $this->assertFalse($cards[1]['isComplete']);
        $this->assertEquals(33, $cards[1]['percent']);
        $this->assertEquals('2 missing', $cards[1]['status']);
        $this->assertEquals('1 of 2 languages still need translations', $languages->summary());
    }

    #[Test]
    public function it_has_nothing_to_translate_in_an_empty_catalogue(): void
    {
        $languages = new CatalogueLanguages(languages: ['en', 'fr'], texts: []);

        $this->assertEquals('Nothing to translate', $languages->cards()[1]['status']);
        $this->assertTrue($languages->cards()[1]['hasNothingMissing']);
        $this->assertFalse($languages->cards()[1]['isComplete']);
        $this->assertEquals('2 languages', $languages->summary());
    }

    #[Test]
    public function it_says_when_every_language_is_complete(): void
    {
        $languages = new CatalogueLanguages(languages: ['en'], texts: [['en' => 'Cuts']]);

        $this->assertEquals('Every language is complete', $languages->summary());
    }
}
