<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\MagicLinkSignInMail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MagicLinkSignInMailTest extends TestCase
{
    #[Test]
    public function it_has_a_subject(): void
    {
        config(['app.name' => 'Jacques']);

        $this->assertEquals(
            'You signed in to Jacques without a password',
            (new MagicLinkSignInMail(ip: '10.0.0.1'))->envelope()->subject,
        );
    }

    #[Test]
    public function it_names_where_the_sign_in_came_from(): void
    {
        $this->assertStringContainsString('10.0.0.1', (new MagicLinkSignInMail(ip: '10.0.0.1'))->render());
    }
}
