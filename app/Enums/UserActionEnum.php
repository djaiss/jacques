<?php

declare(strict_types=1);

namespace App\Enums;

use App\Interfaces\Translatable;

enum UserActionEnum: string implements Translatable
{
    case CompanyCreated = 'company_created';
    case CompanyUpdated = 'company_updated';
    case UserInformationUpdated = 'user_information_updated';
    case UserPasswordUpdated = 'user_password_updated';
    case UserPreferencesUpdated = 'user_preferences_updated';
    case UserDeleted = 'user_deleted';
    case EmployeeCreated = 'employee_created';
    case EmployeeInformationUpdated = 'employee_information_updated';
    case EmployeeAvatarUpdated = 'employee_avatar_updated';
    case EmployeeAvatarDeleted = 'employee_avatar_deleted';
    case LocationCreated = 'location_created';
    case LocationUpdated = 'location_updated';
    case LocationDeleted = 'location_deleted';
    case LocationArchived = 'location_archived';
    case LocationRestored = 'location_restored';
    case UserAssignedToLocation = 'user_assigned_to_location';
    case UserRemovedFromLocation = 'user_removed_from_location';
    case RoleCreated = 'role_created';
    case RoleUpdated = 'role_updated';
    case RoleDeleted = 'role_deleted';
    case RoleAssigned = 'role_assigned';
    case RoleRemoved = 'role_removed';
    case EmailConfirmed = 'email_confirmed';
    case TwoFactorEnabled = 'two_factor_enabled';
    case TwoFactorDisabled = 'two_factor_disabled';
    case TwoFactorRecoveryCodesRegenerated = 'two_factor_recovery_codes_regenerated';
    case UserSignedIn = 'user_signed_in';
    case MagicLinkCreated = 'magic_link_created';
    case ApiKeyCreated = 'api_key_created';
    case ApiKeyDeleted = 'api_key_deleted';
    case WelcomeScreenDismissed = 'welcome_screen_dismissed';

    public function description(): string
    {
        return match ($this) {
            self::CompanyCreated => 'Created the company called :name',
            self::CompanyUpdated => 'Updated the company called :name',
            self::UserInformationUpdated => 'Updated the account of :email',
            self::UserPasswordUpdated => 'Changed their password',
            self::UserPreferencesUpdated => 'Changed their preferences',
            self::UserDeleted => 'Deleted the account of :email',
            self::EmployeeCreated => 'Added :name to the company',
            self::EmployeeInformationUpdated => 'Updated the profile of :name',
            self::EmployeeAvatarUpdated => 'Changed the avatar of :name',
            self::EmployeeAvatarDeleted => 'Removed the avatar of :name',
            self::LocationCreated => 'Created the location called :name',
            self::LocationUpdated => 'Updated the location called :name',
            self::LocationDeleted => 'Deleted the location called :name',
            self::LocationArchived => 'Archived the location called :name',
            self::LocationRestored => 'Reopened the location called :name',
            self::UserAssignedToLocation => 'Assigned :email to the location called :name',
            self::UserRemovedFromLocation => 'Took :email off the location called :name',
            self::RoleCreated => 'Created the role called :name',
            self::RoleUpdated => 'Changed the role called :name',
            self::RoleDeleted => 'Deleted the role called :name',
            self::RoleAssigned => 'Gave the :name role to :email',
            self::RoleRemoved => 'Took the :name role away from :email',
            self::EmailConfirmed => 'Confirmed their email address',
            self::TwoFactorEnabled => 'Turned two factor authentication on',
            self::TwoFactorDisabled => 'Turned two factor authentication off',
            self::TwoFactorRecoveryCodesRegenerated => 'Asked for new recovery codes',
            self::UserSignedIn => 'Signed in',
            self::MagicLinkCreated => 'Asked for a sign-in link',
            self::ApiKeyCreated => 'Created the API key called :name',
            self::ApiKeyDeleted => 'Revoked the API key called :name',
            self::WelcomeScreenDismissed => 'Dismissed the welcome screen',
        };
    }

    /** @return list<string> */
    public function translationKeys(): array
    {
        return [$this->description()];
    }
}
