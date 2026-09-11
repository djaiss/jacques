<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\MagicLinkCreatedMail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MagicLinkCreatedMailTest extends TestCase
{
    #[Test]
    public function it_has_a_subject(): void
    {
        config(['app.name' => 'Jacques']);

        $mailable = new MagicLinkCreatedMail(url: 'https://jacques.test/magic-link/abc', minutes: 5);

        $this->assertEquals('Your sign-in link for Jacques', $mailable->envelope()->subject);
    }

    #[Test]
    public function it_carries_the_link_and_how_long_it_lasts(): void
    {
        $mailable = new MagicLinkCreatedMail(url: 'https://jacques.test/magic-link/abc', minutes: 5);

        $rendered = $mailable->render();

        $this->assertStringContainsString('https://jacques.test/magic-link/abc', $rendered);
        $this->assertStringContainsString('5 minutes', $rendered);
    }
}
