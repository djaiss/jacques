<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\SignInFailedMail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SignInFailedMailTest extends TestCase
{
    #[Test]
    public function it_has_a_subject(): void
    {
        config(['app.name' => 'Jacques']);

        $this->assertEquals(
            'Failed sign-in attempt on your Jacques account',
            (new SignInFailedMail)->envelope()->subject,
        );
    }

    #[Test]
    public function it_tells_the_reader_what_to_do_about_it(): void
    {
        $this->assertStringContainsString('change your password', (new SignInFailedMail)->render());
    }
}
