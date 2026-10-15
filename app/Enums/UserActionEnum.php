<?php

declare(strict_types=1);

namespace App\Enums;

use App\Interfaces\Translatable;

enum UserActionEnum: string implements Translatable
{
    case CompanyCreated = 'company_created';
    case CompanyUpdated = 'company_updated';
    case CompanyLanguagesUpdated = 'company_languages_updated';
    case CompanyCurrenciesUpdated = 'company_currencies_updated';
    case UserInformationUpdated = 'user_information_updated';
    case UserPasswordUpdated = 'user_password_updated';
    case UserPreferencesUpdated = 'user_preferences_updated';
    case UserDeleted = 'user_deleted';
    case StaffCreated = 'staff_created';
    case StaffInformationUpdated = 'staff_information_updated';
    case StaffAvatarUpdated = 'staff_avatar_updated';
    case StaffAvatarDeleted = 'staff_avatar_deleted';
    case StaffPositionUpdated = 'staff_position_updated';
    case StaffActivated = 'staff_activated';
    case StaffDeactivated = 'staff_deactivated';
    case StaffDeleted = 'staff_deleted';
    case LocationCreated = 'location_created';
    case LocationUpdated = 'location_updated';
    case LocationDeleted = 'location_deleted';
    case LocationArchived = 'location_archived';
    case LocationRestored = 'location_restored';
    case StaffAssignedToLocation = 'staff_assigned_to_location';
    case StaffRemovedFromLocation = 'staff_removed_from_location';
    case LocationStaffActivated = 'location_staff_activated';
    case LocationStaffDeactivated = 'location_staff_deactivated';
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
    case OnboardingCompleted = 'onboarding_completed';
    case ServiceCategoryCreated = 'service_category_created';
    case ServiceCategoryUpdated = 'service_category_updated';
    case ServiceCategoryMoved = 'service_category_moved';
    case ServiceCategoryDeleted = 'service_category_deleted';
    case ServiceCreated = 'service_created';
    case ServiceUpdated = 'service_updated';
    case ServiceMoved = 'service_moved';
    case ServiceDeactivated = 'service_deactivated';
    case ServiceActivated = 'service_activated';
    case ServiceDeleted = 'service_deleted';
    case LocationServiceCreated = 'location_service_created';
    case LocationServiceUpdated = 'location_service_updated';
    case LocationServiceDeleted = 'location_service_deleted';
    case StaffLocationServiceAssigned = 'staff_location_service_assigned';
    case StaffLocationServiceRemoved = 'staff_location_service_removed';
    case StaffWorkingHourCreated = 'staff_working_hour_created';
    case StaffWorkingHourUpdated = 'staff_working_hour_updated';
    case StaffWorkingHourDeleted = 'staff_working_hour_deleted';
    case StaffAbsenceCreated = 'staff_absence_created';
    case StaffAbsenceUpdated = 'staff_absence_updated';
    case StaffAbsenceDeleted = 'staff_absence_deleted';

    public function description(): string
    {
        return match ($this) {
            self::CompanyCreated => 'Created the company called :name',
            self::CompanyUpdated => 'Updated the company called :name',
            self::CompanyLanguagesUpdated => 'Changed the languages of the salon to :languages',
            self::CompanyCurrenciesUpdated => 'Changed the currencies of the salon to :currencies',
            self::UserInformationUpdated => 'Updated the account of :email',
            self::UserPasswordUpdated => 'Changed their password',
            self::UserPreferencesUpdated => 'Changed their preferences',
            self::UserDeleted => 'Deleted the account of :email',
            self::StaffCreated => 'Added :name to the company',
            self::StaffInformationUpdated => 'Updated the profile of :name',
            self::StaffAvatarUpdated => 'Changed the avatar of :name',
            self::StaffAvatarDeleted => 'Removed the avatar of :name',
            self::StaffPositionUpdated => 'Moved :name in the order of the team',
            self::StaffActivated => 'Reactivated :name',
            self::StaffDeactivated => 'Deactivated :name',
            self::StaffDeleted => 'Deleted :name from the company',
            self::LocationCreated => 'Created the location called :name',
            self::LocationUpdated => 'Updated the location called :name',
            self::LocationDeleted => 'Deleted the location called :name',
            self::LocationArchived => 'Archived the location called :name',
            self::LocationRestored => 'Reopened the location called :name',
            self::StaffAssignedToLocation => 'Assigned :staff to the location called :name',
            self::StaffRemovedFromLocation => 'Took :staff off the location called :name',
            self::LocationStaffActivated => 'Resumed the assignment of :staff to the location called :name',
            self::LocationStaffDeactivated => 'Paused the assignment of :staff to the location called :name',
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
            self::OnboardingCompleted => 'Completed the onboarding',
            self::ServiceCategoryCreated => 'Created the service category called :name',
            self::ServiceCategoryUpdated => 'Updated the service category called :name',
            self::ServiceCategoryMoved => 'Moved the service category called :name',
            self::ServiceCategoryDeleted => 'Deleted the service category called :name',
            self::ServiceCreated => 'Created the service called :name',
            self::ServiceUpdated => 'Updated the service called :name',
            self::ServiceMoved => 'Moved the service called :name',
            self::ServiceDeactivated => 'Deactivated the service called :name',
            self::ServiceActivated => 'Activated the service called :name',
            self::ServiceDeleted => 'Deleted the service called :name',
            self::LocationServiceCreated => 'Offered the service called :name at the location called :location',
            self::LocationServiceUpdated => 'Updated the service called :name at the location called :location',
            self::LocationServiceDeleted => 'Stopped offering the service called :name at the location called :location',
            self::StaffLocationServiceAssigned => 'Let :staff perform the service called :name at the location called :location',
            self::StaffLocationServiceRemoved => 'Stopped :staff performing the service called :name at the location called :location',
            self::StaffWorkingHourCreated => 'Added working hours for :name at the location called :location',
            self::StaffWorkingHourUpdated => 'Changed working hours of :name at the location called :location',
            self::StaffWorkingHourDeleted => 'Removed working hours of :name at the location called :location',
            self::StaffAbsenceCreated => 'Recorded an absence of :name',
            self::StaffAbsenceUpdated => 'Changed an absence of :name',
            self::StaffAbsenceDeleted => 'Removed an absence of :name',
        };
    }

    /** @return list<string> */
    public function translationKeys(): array
    {
        return [$this->description()];
    }
}
