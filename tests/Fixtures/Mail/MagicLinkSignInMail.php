<?php

declare(strict_types=1);

namespace Tests\Fixtures\Mail;

use App\Interfaces\HasEnvelope;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class MagicLinkSignInMail extends Mailable implements HasEnvelope
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'A sign-in without a password on your Jacques account',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>Someone just signed in. <a href="https://jacques.test">Was this you?</a></p>',
        );
    }
}
