<?php

declare(strict_types=1);

namespace App\Enums;

enum EmailTypeEnum: string
{
    case SignInFailed = 'sign_in_failed';
    case MagicLinkSignIn = 'magic_link_sign_in';
    case SignInFromNewAddress = 'sign_in_from_new_address';
    case MagicLinkCreated = 'magic_link_created';
    case EmailVerification = 'email_verification';
    case StaffInvitation = 'staff_invitation';
}
