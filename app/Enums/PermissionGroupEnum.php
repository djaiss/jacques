<?php

declare(strict_types=1);

namespace App\Enums;

use App\Interfaces\Translatable;

enum PermissionGroupEnum: string implements Translatable
{
    case Administration = 'administration';
    case Locations = 'locations';
    case Team = 'team';
    case Services = 'services';
    case Clients = 'clients';
    case Appointments = 'appointments';
    case Reports = 'reports';

    public function label(): string
    {
        return match ($this) {
            self::Administration => 'Administration',
            self::Locations => 'Locations',
            self::Team => 'Team',
            self::Services => 'Services',
            self::Clients => 'Clients',
            self::Appointments => 'Appointments',
            self::Reports => 'Reports',
        };
    }

    public function note(): string
    {
        return match ($this) {
            self::Administration => 'The subscription, the company and the way access to it is handed out',
            self::Locations => 'Where the salon works, when it opens and who works when',
            self::Team => 'The people of the salon and their profiles',
            self::Services => 'What the salon offers and at what price',
            self::Clients => 'The people who book with the salon',
            self::Appointments => 'The agenda and the bookings in it',
            self::Reports => 'What the salon did, in numbers',
        };
    }

    /** @return list<string> */
    public function translationKeys(): array
    {
        return [$this->label(), $this->note()];
    }
}
