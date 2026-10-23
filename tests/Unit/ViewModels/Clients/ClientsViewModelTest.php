<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Clients\ClientsViewModelTest;

use App\Enums\AppointmentStatusEnum;
use App\Enums\ClientSegmentEnum;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use App\ViewModels\Clients\ClientsViewModel;

function owner(): User
{
    $company = Company::factory()->create(['name' => 'Dunder Mifflin']);

    return test()->giveDefaultRole(User::factory()->create(['company_id' => $company->id]), Role::OWNER);
}

function location(User $owner, string $name): Location
{
    return Location::factory()->create(['company_id' => $owner->company_id, 'name' => $name, 'timezone' => 'America/New_York']);
}

function client(User $owner, string $firstName, string $lastName, array $attributes = []): Client
{
    return Client::factory()->create(['company_id' => $owner->company_id, 'first_name' => $firstName, 'last_name' => $lastName, ...$attributes]);
}

function appointment(Client $client, Location $location, string $startsAt, AppointmentStatusEnum $status = AppointmentStatusEnum::Scheduled, ?Staff $staff = null): Appointment
{
    return Appointment::factory()->create([
        'company_id' => $client->company_id,
        'location_id' => $location->id,
        'client_id' => $client->id,
        'staff_id' => $staff->id ?? Staff::factory()->create(['company_id' => $client->company_id])->id,
        'starts_at' => $startsAt,
        'ends_at' => $startsAt,
        'status' => $status,
    ]);
}

/** @return array{owner: User, scranton: Location, nashua: Location} */
function office(): array
{
    test()->travelTo('2026-04-01 12:00:00');
    $owner = owner();
    $scranton = location($owner, 'Scranton');
    $nashua = location($owner, 'Nashua');

    $pam = client($owner, 'Pam', 'Beesly');
    appointment($pam, $scranton, '2026-01-10 15:00:00');
    appointment($pam, $scranton, '2026-02-20 15:00:00');
    appointment($pam, $scranton, '2026-03-25 15:00:00');
    appointment($pam, $scranton, '2026-04-06 15:00:00');

    $kevin = client($owner, 'Kevin', 'Malone');
    appointment($kevin, $nashua, '2025-09-01 15:00:00');

    $angela = client($owner, 'Angela', 'Martin', ['phone' => '+15705550100', 'email' => 'angela@dundermifflin.com']);
    appointment($angela, $scranton, '2026-03-31 15:00:00');

    $dwight = client($owner, 'Dwight', 'Schrute');
    appointment($dwight, $scranton, '2026-03-20 15:00:00', AppointmentStatusEnum::Cancelled);

    return ['owner' => $owner, 'scranton' => $scranton, 'nashua' => $nashua];
}

/** @return list<string> */
function names(ClientsViewModel $viewModel): array
{
    return array_column($viewModel->rows(), 'name');
}

it('lists every client by last visit, those without a visit last', function () {
    ['owner' => $owner] = office();

    $viewModel = new ClientsViewModel(user: $owner);

    expect(names($viewModel))->toBe(['Angela Martin', 'Pam Beesly', 'Kevin Malone', 'Dwight Schrute']);
    expect($viewModel->footer())->toBe('4 clients across all salons.');
});

it('describes the last visit and the next appointment of a client', function () {
    ['owner' => $owner] = office();

    $rows = collect((new ClientsViewModel(user: $owner))->rows())->keyBy('name');

    expect($rows['Pam Beesly']['salon'])->toBe('Scranton');
    expect($rows['Pam Beesly']['lastVisit'])->toBe('Mar 25, 2026');
    expect($rows['Pam Beesly']['lastVisitAgo'])->toBe('6 days ago');
    expect($rows['Pam Beesly']['nextAppointment'])->toBe('Apr 6, 2026');
    expect($rows['Pam Beesly']['visitCount'])->toBe(3);
    expect($rows['Kevin Malone']['nextAppointment'])->toBeNull();
    expect($rows['Angela Martin']['phone'])->toBe('+1 570-555-0100');
});

it('never counts a cancelled appointment as a visit', function () {
    ['owner' => $owner] = office();

    $dwight = collect((new ClientsViewModel(user: $owner))->rows())->firstWhere('name', 'Dwight Schrute');

    expect($dwight['visitCount'])->toBe(0);
    expect($dwight['lastVisit'])->toBeNull();
    expect($dwight['nextAppointment'])->toBeNull();
});

it('leaves out the clients of another company', function () {
    ['owner' => $owner] = office();
    $vance = owner();
    client($vance, 'Bob', 'Vance');

    expect(names(new ClientsViewModel(user: $owner)))->not->toContain('Bob Vance');
});

it('shows only the clients of their own appointments to somebody seeing themselves only', function () {
    ['owner' => $owner, 'scranton' => $scranton] = office();
    $kelly = User::factory()->create(['company_id' => $owner->company_id]);
    $kellyStaff = Staff::factory()->create(['company_id' => $owner->company_id, 'user_id' => $kelly->id]);
    $this->grant($kelly, PermissionEnum::ClientView, ScopeEnum::Self);
    appointment(Client::query()->where('first_name', 'Pam')->first(), $scranton, '2026-03-28 15:00:00', staff: $kellyStaff);

    expect(names(new ClientsViewModel(user: $kelly->refresh())))->toBe(['Pam Beesly']);
});

it('shows nobody to an account without staff record that sees itself only', function () {
    ['owner' => $owner] = office();
    $david = User::factory()->create(['company_id' => $owner->company_id]);
    $this->grant($david, PermissionEnum::ClientView, ScopeEnum::Self);

    expect((new ClientsViewModel(user: $david))->rows())->toBe([]);
});

it('names an erased client without their data and never finds them', function () {
    ['owner' => $owner] = office();
    client($owner, 'Toby', 'Flenderson')->update(['first_name' => null, 'last_name' => null, 'phone' => null, 'anonymized_at' => now()]);

    expect(names(new ClientsViewModel(user: $owner)))->toContain('Erased client');
    expect(names(new ClientsViewModel(user: $owner, search: 'erased')))->toBe([]);
});

it('finds a client by name, email or phone', function (string $search, array $found) {
    ['owner' => $owner] = office();
    client($owner, 'Oscar', 'Martínez');

    expect(names(new ClientsViewModel(user: $owner, search: $search)))->toEqualCanonicalizing($found);
})->with('searches');

dataset('searches', [
    'first name in lower case' => ['angela', ['Angela Martin']],
    'full name' => ['dwight schrute', ['Dwight Schrute']],
    'name without its accent' => ['martinez', ['Oscar Martínez']],
    'email' => ['dundermifflin.com', ['Angela Martin']],
    'end of the phone' => ['555 0100', ['Angela Martin']],
    'national phone' => ['(570) 555-0100', ['Angela Martin']],
    'international phone' => ['+15705550100', ['Angela Martin']],
    'two digits only' => ['01', []],
]);

it('says that nobody matches a search', function () {
    ['owner' => $owner, 'scranton' => $scranton] = office();

    expect((new ClientsViewModel(user: $owner, search: 'xyz'))->emptyText())->toBe('Nobody matches “xyz” across all salons.');
    expect((new ClientsViewModel(user: $owner, location: $scranton, search: 'xyz'))->emptyText())->toBe('Nobody matches “xyz” at Scranton.');
});

it('narrows the list to the clients of one salon', function () {
    ['owner' => $owner, 'nashua' => $nashua] = office();

    $viewModel = new ClientsViewModel(user: $owner, location: $nashua);

    expect(names($viewModel))->toBe(['Kevin Malone']);
    expect($viewModel->footer())->toBe('1 client at Nashua.');
});

it('offers the salons that are not archived', function () {
    ['owner' => $owner] = office();
    location($owner, 'Stamford')->update(['archived_at' => now()]);

    expect(array_column((new ClientsViewModel(user: $owner))->locations(), 'name'))->toBe(['Nashua', 'Scranton']);
});

it('counts the clients of each tab', function () {
    ['owner' => $owner] = office();

    $tabs = collect((new ClientsViewModel(user: $owner))->tabs())->pluck('count', 'key');

    expect($tabs->all())->toBe(['all' => '4', 'new' => '2', 'regular' => '1', 'inactive' => '1', 'notRebooked' => '3']);
});

it('counts the clients of each tab under the search', function () {
    ['owner' => $owner] = office();

    $tabs = collect((new ClientsViewModel(user: $owner, search: 'pam'))->tabs())->pluck('count', 'key');

    expect($tabs->all())->toBe(['all' => '1', 'new' => '0', 'regular' => '1', 'inactive' => '0', 'notRebooked' => '0']);
});

it('lists the clients of a tab', function (?ClientSegmentEnum $segment, bool $notRebooked, array $found) {
    ['owner' => $owner] = office();

    expect(names(new ClientsViewModel(user: $owner, segment: $segment, notRebooked: $notRebooked)))->toBe($found);
})->with('tabs');

dataset('tabs', [
    'new' => [ClientSegmentEnum::New, false, ['Angela Martin', 'Dwight Schrute']],
    'regular' => [ClientSegmentEnum::Regular, false, ['Pam Beesly']],
    'inactive' => [ClientSegmentEnum::Inactive, false, ['Kevin Malone']],
    'not rebooked' => [null, true, ['Angela Martin', 'Kevin Malone', 'Dwight Schrute']],
]);

it('keeps the search and the salon in the link of every tab', function () {
    ['owner' => $owner, 'scranton' => $scranton] = office();

    $tabs = collect((new ClientsViewModel(user: $owner, segment: ClientSegmentEnum::Inactive, location: $scranton, search: 'pam'))->tabs())->keyBy('key');

    expect($tabs['regular']['url'])->toBe(route('clientSegments.show', ['segment' => 'regular', 'q' => 'pam', 'location' => $scranton->id]));
    expect($tabs['notRebooked']['url'])->toBe(route('clientsNotRebooked.index', ['q' => 'pam', 'location' => $scranton->id]));
    expect($tabs['inactive']['isCurrent'])->toBeTrue();
    expect($tabs['all']['isCurrent'])->toBeFalse();
});

it('gives the text of an empty tab', function () {
    $this->travelTo('2026-04-01 12:00:00');

    expect((new ClientsViewModel(user: owner()))->emptyText())->toBe('Clients appear here after their first booking.');
    expect((new ClientsViewModel(user: owner(), notRebooked: true))->emptyText())->toBe('Every client has an appointment coming up.');
});

it('cuts the list into pages of fifty', function () {
    $this->travelTo('2026-04-01 12:00:00');
    $owner = owner();
    Client::factory()->count(51)->create(['company_id' => $owner->company_id]);

    expect((new ClientsViewModel(user: $owner))->rows())->toHaveCount(50);
    expect((new ClientsViewModel(user: $owner, page: 2))->rows())->toHaveCount(1);
    expect((new ClientsViewModel(user: $owner, page: 2))->hasPage())->toBeTrue();
    expect((new ClientsViewModel(user: $owner, page: 3))->hasPage())->toBeFalse();
});
