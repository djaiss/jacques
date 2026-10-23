<?php

declare(strict_types=1);

namespace App\ViewModels\Dashboard;

use Carbon\CarbonImmutable;
use Random\Engine\Mt19937;
use Random\Randomizer;

/*
 * The agenda shows invented hairdressers and appointments until the salon has
 * real ones. Each day is drawn from a generator seeded with the hairdresser and
 * the date, so the same day always reads the same.
 */
class GeneratedAgenda
{
    public const int OPENING = 9 * 60;

    public const int CLOSING = 19 * 60;

    public const int LUNCH_LENGTH = 60;

    /**
     * @return list<array{id: string, name: string, hue: int, visible: bool}>
     */
    public function hairdressers(): array
    {
        return [
            ['id' => 'amelie', 'name' => 'Amélie', 'hue' => 310, 'visible' => true],
            ['id' => 'sammy', 'name' => 'Sammy', 'hue' => 250, 'visible' => true],
            ['id' => 'charlotte', 'name' => 'Charlotte', 'hue' => 200, 'visible' => true],
            ['id' => 'alexandra', 'name' => 'Alexandra', 'hue' => 80, 'visible' => false],
            ['id' => 'lucas', 'name' => 'Lucas', 'hue' => 30, 'visible' => true],
        ];
    }

    /**
     * @return list<array{name: string, duration: int}>
     */
    public function services(): array
    {
        return [
            ['name' => __('Women\'s cut'), 'duration' => 45],
            ['name' => __('Men\'s cut'), 'duration' => 30],
            ['name' => __('Blow dry'), 'duration' => 30],
            ['name' => __('Colour'), 'duration' => 90],
            ['name' => __('Highlights'), 'duration' => 120],
            ['name' => __('Beard trim'), 'duration' => 20],
            ['name' => __('Child\'s cut'), 'duration' => 20],
            ['name' => __('Deep treatment'), 'duration' => 30],
        ];
    }

    /**
     * @return list<string>
     */
    public function clients(): array
    {
        return [
            'Julie Bernard', 'Marc Petit', 'Léa Dubois', 'Hugo Moreau', 'Sarah Laurent',
            'Nina Garcia', 'Paul Roux', 'Inès Fournier', 'Tom Girard', 'Emma Lefèvre',
            'Yanis Mercier', 'Chloé Blanc', 'Lucie Faure', 'Noah André', 'Zoé Martin',
        ];
    }

    /**
     * The day of one hairdresser, in minutes from midnight, or null on a
     * Sunday, when the salon is closed.
     *
     * @return array{absent: bool, start: int, end: int, lunchStart: int, appointments: list<array{id: string, start: int, duration: int, client: string, service: string}>}|null
     */
    public function plan(string $hairdresserId, CarbonImmutable $date): ?array
    {
        if ($date->isSunday()) {
            return null;
        }

        $randomizer = new Randomizer(new Mt19937(crc32("{$hairdresserId}{$date->format('Y-m-d')}")));

        $absent = $randomizer->nextFloat() < 0.1;
        $start = $randomizer->nextFloat() < 0.6 ? 9 * 60 : 10 * 60;
        $end = $randomizer->nextFloat() < 0.5 ? 18 * 60 : 19 * 60;
        $lunchStart = $randomizer->nextFloat() < 0.5 ? 12 * 60 + 30 : 13 * 60;
        $lunchEnd = $lunchStart + self::LUNCH_LENGTH;

        $services = $this->services();
        $clients = $this->clients();
        $gaps = [15, 30, 45, 60];

        if ($absent) {
            return ['absent' => true, 'start' => $start, 'end' => $end, 'lunchStart' => $lunchStart, 'appointments' => []];
        }

        $appointments = [];
        $time = $start;

        while ($time < $end) {
            if ($randomizer->nextFloat() > 0.6) {
                $time += $gaps[$randomizer->getInt(0, count($gaps) - 1)];
            }

            $service = $services[$randomizer->getInt(0, count($services) - 1)];

            if ($time < $lunchEnd && $time + $service['duration'] > $lunchStart) {
                $time = $lunchEnd;

                continue;
            }

            if ($time + $service['duration'] > $end) {
                break;
            }

            $appointments[] = [
                'id' => "{$hairdresserId}-{$date->format('Y-m-d')}-".count($appointments),
                'start' => $time,
                'duration' => $service['duration'],
                'client' => $clients[$randomizer->getInt(0, count($clients) - 1)],
                'service' => $service['name'],
            ];

            $time += $service['duration'];
        }

        return ['absent' => false, 'start' => $start, 'end' => $end, 'lunchStart' => $lunchStart, 'appointments' => $appointments];
    }
}
