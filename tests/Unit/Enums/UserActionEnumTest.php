<?php

declare(strict_types=1);

use App\Enums\UserActionEnum;

it('describes every action', function () {
    foreach (UserActionEnum::cases() as $action) {
        expect($action->description())->not->toBeEmpty();
    }
});

it('describes an action with the parameters it was logged with', function () {
    expect(UserActionEnum::CompanyLanguagesUpdated->description())->toEqual('Changed the languages of the salon to :languages');
    expect(UserActionEnum::CompanyCurrenciesUpdated->description())->toEqual('Changed the currencies of the salon to :currencies');
    expect(UserActionEnum::StaffCreated->description())->toEqual('Added :name to the company');
    expect(UserActionEnum::StaffInformationUpdated->description())->toEqual('Updated the profile of :name');
    expect(UserActionEnum::StaffAvatarUpdated->description())->toEqual('Changed the avatar of :name');
    expect(UserActionEnum::StaffAvatarDeleted->description())->toEqual('Removed the avatar of :name');
    expect(UserActionEnum::StaffAssignedToLocation->description())->toEqual('Assigned :staff to the location called :name');
    expect(UserActionEnum::StaffRemovedFromLocation->description())->toEqual('Took :staff off the location called :name');
    expect(UserActionEnum::LocationStaffDeactivated->description())->toEqual('Paused the assignment of :staff to the location called :name');
    expect(UserActionEnum::LocationServiceCreated->description())->toEqual('Offered the service called :name at the location called :location');
    expect(UserActionEnum::StaffLocationServiceAssigned->description())->toEqual('Let :staff perform the service called :name at the location called :location');
    expect(UserActionEnum::StaffLocationServiceRemoved->description())->toEqual('Stopped :staff performing the service called :name at the location called :location');
});
