<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\DefaultCatalogue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DefaultCatalogueTest extends TestCase
{
    #[Test]
    public function it_lists_six_categories_and_seventeen_services(): void
    {
        $categories = DefaultCatalogue::categories();

        $this->assertCount(6, $categories);
        $this->assertCount(17, array_merge(...array_column($categories, 'services')));
    }

    #[Test]
    public function it_gives_every_service_at_least_one_step_that_requires_a_team_member(): void
    {
        foreach (DefaultCatalogue::categories() as $category) {
            foreach ($category['services'] as $service) {
                $this->assertContains(true, array_column($service['steps'], 'requires_staff'));
            }
        }
    }

    #[Test]
    public function it_writes_a_text_in_every_interface_locale(): void
    {
        $texts = DefaultCatalogue::texts(fn (string $locale): string => __('Processing', locale: $locale));

        $this->assertEqualsCanonicalizing(['en', 'fr', 'de', 'es'], array_keys($texts));
        $this->assertEquals('Processing', $texts['en']);
    }
}
