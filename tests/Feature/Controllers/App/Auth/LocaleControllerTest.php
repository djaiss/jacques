<?php

declare(strict_types=1);

it('changes the language of the interface', function () {
    $response = $this->from(route('auth.register.new'))
        ->put(route('auth.locale.update'), ['locale' => 'fr_FR']);

    $response->assertRedirect(route('auth.register.new'));
    $response->assertSessionHas('locale', 'fr_FR');

    $this->get(route('auth.register.new'))->assertSee('Créez votre compte');
});

it('refuses a language the interface does not speak', function () {
    $response = $this->put(route('auth.locale.update'), ['locale' => 'sv_SE']);

    $response->assertSessionHasErrors('locale');
    expect(session('locale'))->toBeNull();
});
