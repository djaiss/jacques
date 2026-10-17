<?php

declare(strict_types=1);

it('finds the locale files up to date', function () {
    $this->artisan('jacques:localize', ['--check' => true])
        ->expectsOutputToContain('The locale files are up to date.')
        ->assertSuccessful();
});

it('collects the wording of a translatable enum', function () {
    $english = json_decode((string) file_get_contents(lang_path('en.json')), true);

    expect($english)->toHaveKey('Book and change appointments');
    expect($english)->toHaveKey('Their locations');
});

it('writes nothing when asked to check', function () {
    $before = (string) file_get_contents(lang_path('fr_FR.json'));

    $this->artisan('jacques:localize', ['--check' => true]);

    expect(file_get_contents(lang_path('fr_FR.json')))->toBe($before);
});
