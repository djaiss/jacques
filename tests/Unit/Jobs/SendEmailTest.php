<?php

declare(strict_types=1);

use App\Enums\EmailTypeEnum;
use App\Jobs\SendEmail;
use App\Models\Company;
use App\Models\EmailSent;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Resend\Email;
use Resend\Service\Email as EmailService;
use Tests\Fixtures\Mail\MagicLinkSignInMail;

it('sends the email the traditional way', function () {
    Config::set('app.use_resend', false);
    Config::set('mail.from.address', 'noreply@jacques.test');
    Mail::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'michael.scott@dundermifflin.com',
    ]);

    new SendEmail(
        mailable: new MagicLinkSignInMail,
        company: $company,
        emailType: EmailTypeEnum::MagicLinkSignIn,
        user: $user,
    )->handle();

    Mail::assertSent(
        MagicLinkSignInMail::class,
        fn (MagicLinkSignInMail $mail): bool => $mail->hasTo('michael.scott@dundermifflin.com'),
    );

    $emailSent = EmailSent::query()->latest()->first();

    expect($emailSent->company_id)->toEqual($company->id);
    expect($emailSent->user_id)->toEqual($user->id);
    expect($emailSent->email_type)->toEqual(EmailTypeEnum::MagicLinkSignIn->value);
    expect($emailSent->email_address)->toEqual('michael.scott@dundermifflin.com');
    expect($emailSent->subject)->toEqual('A sign-in without a password on your Jacques account');
    expect($emailSent->uuid)->toBeNull();
    expect($emailSent->sent_at)->not->toBeNull();
});

it('sends the email with resend', function () {
    Config::set('app.use_resend', true);
    Config::set('mail.from.address', 'noreply@jacques.test');

    $emailsMock = Mockery::mock(EmailService::class);
    $emailsMock
        ->shouldReceive('send')
        ->once()
        ->with(Mockery::on(
            fn ($arguments): bool => (
                $arguments['from'] === 'noreply@jacques.test'
                && $arguments['to'] === ['michael.scott@dundermifflin.com']
                && $arguments['subject'] === 'A sign-in without a password on your Jacques account'
                && is_string($arguments['html'])
                && mb_strlen($arguments['html']) > 0
            ),
        ))
        ->andReturn(Email::from(['id' => 'resend-uuid-12345']));

    $resendMock = Mockery::mock();
    $resendMock
        ->shouldReceive('emails')
        ->once()
        ->andReturn($emailsMock);

    app()->instance('resend', $resendMock);

    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'michael.scott@dundermifflin.com',
    ]);

    new SendEmail(
        mailable: new MagicLinkSignInMail,
        company: $company,
        emailType: EmailTypeEnum::MagicLinkSignIn,
        user: $user,
    )->handle();

    $emailSent = EmailSent::query()->latest()->first();

    expect($emailSent->uuid)->toEqual('resend-uuid-12345');
    expect($emailSent->email_address)->toEqual('michael.scott@dundermifflin.com');
});

it('sends the email to an address that has no user', function () {
    Config::set('app.use_resend', false);
    Mail::fake();

    $company = Company::factory()->create();

    new SendEmail(
        mailable: new MagicLinkSignInMail,
        company: $company,
        emailType: EmailTypeEnum::MagicLinkCreated,
        emailAddress: 'jan.levinson@dundermifflin.com',
    )->handle();

    Mail::assertSent(
        MagicLinkSignInMail::class,
        fn (MagicLinkSignInMail $mail): bool => $mail->hasTo('jan.levinson@dundermifflin.com'),
    );

    $emailSent = EmailSent::query()->latest()->first();

    expect($emailSent->company_id)->toEqual($company->id);
    expect($emailSent->user_id)->toBeNull();
    expect($emailSent->email_address)->toEqual('jan.levinson@dundermifflin.com');
});

it('prefers the given address over the email of the user', function () {
    Config::set('app.use_resend', false);
    Mail::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'michael.scott@dundermifflin.com',
    ]);

    new SendEmail(
        mailable: new MagicLinkSignInMail,
        company: $company,
        emailType: EmailTypeEnum::MagicLinkSignIn,
        user: $user,
        emailAddress: 'michael.scarn@dundermifflin.com',
    )->handle();

    $emailSent = EmailSent::query()->latest()->first();

    expect($emailSent->user_id)->toEqual($user->id);
    expect($emailSent->email_address)->toEqual('michael.scarn@dundermifflin.com');
});

it('throws when there is neither a user nor an address', function () {

    Config::set('app.use_resend', false);
    Mail::fake();

    $company = Company::factory()->create();

    new SendEmail(
        mailable: new MagicLinkSignInMail,
        company: $company,
        emailType: EmailTypeEnum::MagicLinkSignIn,
    )->handle();
})->throws(InvalidArgumentException::class);
