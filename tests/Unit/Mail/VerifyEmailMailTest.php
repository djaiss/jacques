<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\VerifyEmailMail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VerifyEmailMailTest extends TestCase
{
    #[Test]
    public function it_has_a_subject(): void
    {
        $mailable = new VerifyEmailMail(url: 'https://jacques.test/verify-email/1/abc');

        $this->assertEquals('Confirm your email address', $mailable->envelope()->subject);
    }

    #[Test]
    public function it_carries_the_link_to_confirm_the_address(): void
    {
        $mailable = new VerifyEmailMail(url: 'https://jacques.test/verify-email/1/abc');

        $this->assertStringContainsString('https://jacques.test/verify-email/1/abc', $mailable->render());
    }
}
