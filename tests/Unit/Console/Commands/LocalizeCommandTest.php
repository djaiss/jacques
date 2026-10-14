<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocalizeCommandTest extends TestCase
{
    #[Test]
    public function it_finds_the_locale_files_up_to_date(): void
    {
        $this->artisan('jacques:localize', ['--check' => true])
            ->expectsOutputToContain('The locale files are up to date.')
            ->assertSuccessful();
    }

    #[Test]
    public function it_collects_the_wording_of_a_translatable_enum(): void
    {
        $english = json_decode((string) file_get_contents(lang_path('en.json')), true);

        $this->assertArrayHasKey('Book and change appointments', $english);
        $this->assertArrayHasKey('Their locations', $english);
    }

    #[Test]
    public function it_writes_nothing_when_asked_to_check(): void
    {
        $before = (string) file_get_contents(lang_path('fr_FR.json'));

        $this->artisan('jacques:localize', ['--check' => true]);

        $this->assertSame($before, file_get_contents(lang_path('fr_FR.json')));
    }
}
