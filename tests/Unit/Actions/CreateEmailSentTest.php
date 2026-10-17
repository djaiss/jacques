<?php

declare(strict_types=1);

use App\Actions\CreateEmailSent;
use App\Enums\EmailTypeEnum;
use App\Models\Company;
use App\Models\EmailSent;
use App\Models\User;
use Illuminate\Support\Facades\Date;

it('creates an email sent', function () {
    Date::setTestNow(Date::create(2026, 7, 31));

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $emailSent = new CreateEmailSent(
        company: $company,
        user: $user,
        uuid: 'd27cee22-b10f-46c4-a7dc-af3b46820d80',
        emailType: EmailTypeEnum::MagicLinkSignIn->value,
        emailAddress: 'michael.scott@dundermifflin.com',
        subject: 'A sign-in without a password on your account',
        body: 'Someone just signed in.',
    )->execute();

    expect($emailSent)->toBeInstanceOf(EmailSent::class);
    $this->assertDatabaseHas('emails_sent', [
        'id' => $emailSent->id,
        'company_id' => $company->id,
        'user_id' => $user->id,
        'uuid' => 'd27cee22-b10f-46c4-a7dc-af3b46820d80',
        'email_type' => EmailTypeEnum::MagicLinkSignIn->value,
        'email_address' => 'michael.scott@dundermifflin.com',
        'subject' => 'A sign-in without a password on your account',
        'sent_at' => '2026-07-31 00:00:00',
    ]);
});

it('creates an email sent without a user', function () {
    $company = Company::factory()->create();

    $emailSent = new CreateEmailSent(
        company: $company,
        user: null,
        uuid: null,
        emailType: EmailTypeEnum::MagicLinkCreated->value,
        emailAddress: 'jan.levinson@dundermifflin.com',
        subject: 'Your magic link',
        body: 'Here is your link.',
    )->execute();

    $this->assertDatabaseHas('emails_sent', [
        'id' => $emailSent->id,
        'company_id' => $company->id,
        'user_id' => null,
        'uuid' => null,
    ]);
});
