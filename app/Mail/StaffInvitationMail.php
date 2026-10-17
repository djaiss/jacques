<?php

declare(strict_types=1);

namespace App\Mail;

use App\Helpers\StaffInvitationCopy;
use App\Interfaces\HasEnvelope;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class StaffInvitationMail extends Mailable implements HasEnvelope
{
    public function __construct(
        public readonly string $url,
        public readonly string $inviter,
        public readonly string $salon,
        public readonly string $role,
        public readonly string $firstName,
        public readonly int $days,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->copy()->subject(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.staff-invitation',
            with: [
                'url' => $this->url,
                'salon' => $this->salon,
                'role' => $this->role,
                'firstName' => $this->firstName,
                'copy' => $this->copy(),
            ],
        );
    }

    private function copy(): StaffInvitationCopy
    {
        return new StaffInvitationCopy(
            inviter: $this->inviter,
            salon: $this->salon,
            days: $this->days,
        );
    }
}
