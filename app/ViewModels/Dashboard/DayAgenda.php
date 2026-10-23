<?php

declare(strict_types=1);

namespace App\ViewModels\Dashboard;

use Carbon\CarbonImmutable;

class DayAgenda
{
    public const float PIXELS_PER_MINUTE = 1.5;

    /*
     * Under this height a block has no room for three lines, so the time, the
     * client and the service sit on one.
     */
    private const int COMPACT_HEIGHT = 44;

    public function __construct(
        private readonly CarbonImmutable $date,
        private readonly GeneratedAgenda $agenda,
    ) {}

    public function height(): int
    {
        return $this->offset(GeneratedAgenda::CLOSING);
    }

    public function hourHeight(): int
    {
        return (int) (60 * self::PIXELS_PER_MINUTE);
    }

    /**
     * @return list<array{label: string, top: int}>
     */
    public function hours(): array
    {
        $hours = [];

        for ($minutes = GeneratedAgenda::OPENING; $minutes <= GeneratedAgenda::CLOSING; $minutes += 60) {
            $hours[] = ['label' => $this->time($minutes), 'top' => $this->offset($minutes)];
        }

        return $hours;
    }

    /**
     * One column per hairdresser, every one of them, the screen hiding those
     * that are unchecked. Empty on a Sunday.
     *
     * @return list<array<string, mixed>>
     */
    public function columns(): array
    {
        $columns = [];

        foreach ($this->agenda->hairdressers() as $hairdresser) {
            $plan = $this->agenda->plan($hairdresser['id'], $this->date);

            if ($plan === null) {
                return [];
            }

            $lunchEnd = $plan['lunchStart'] + GeneratedAgenda::LUNCH_LENGTH;
            $count = count($plan['appointments']);

            $columns[] = [
                'id' => $hairdresser['id'],
                'name' => $hairdresser['name'],
                'hue' => $hairdresser['hue'],
                'visible' => $hairdresser['visible'],
                'absent' => $plan['absent'],
                'count' => $count,
                'countLabel' => $plan['absent']
                    ? __('Absent all day')
                    : trans_choice(':count appointment|:count appointments', $count),
                'hoursLabel' => $plan['absent']
                    ? null
                    : __(':start to :end', ['start' => $this->time($plan['start']), 'end' => $this->time($plan['end'])]),
                'lunchLabel' => $plan['absent']
                    ? null
                    : __('Lunch :time', ['time' => $this->time($plan['lunchStart'])]),
                'offBlocks' => $plan['absent']
                    ? [$this->offBlock(GeneratedAgenda::OPENING, GeneratedAgenda::CLOSING, __('On leave'))]
                    : array_values(array_filter([
                        $plan['start'] > GeneratedAgenda::OPENING ? $this->offBlock(GeneratedAgenda::OPENING, $plan['start'], __('Not arrived yet')) : null,
                        $this->offBlock($plan['lunchStart'], $lunchEnd, __('Lunch break')),
                        $plan['end'] < GeneratedAgenda::CLOSING ? $this->offBlock($plan['end'], GeneratedAgenda::CLOSING, __('Gone')) : null,
                    ])),
                'workingRanges' => $plan['absent']
                    ? []
                    : [[$plan['start'], $plan['lunchStart']], [$lunchEnd, $plan['end']]],
                'appointments' => array_map(
                    fn (array $appointment): array => $this->appointment($appointment, $hairdresser['name']),
                    $plan['appointments'],
                ),
            ];
        }

        return $columns;
    }

    /**
     * @param  array{id: string, start: int, duration: int, client: string, service: string}  $appointment
     * @return array<string, mixed>
     */
    private function appointment(array $appointment, string $hairdresser): array
    {
        $height = (int) round($appointment['duration'] * self::PIXELS_PER_MINUTE) - 2;
        $range = __(':start to :end', [
            'start' => $this->time($appointment['start']),
            'end' => $this->time($appointment['start'] + $appointment['duration']),
        ]);

        return [
            'id' => $appointment['id'],
            'start' => $appointment['start'],
            'duration' => $appointment['duration'],
            'top' => $this->offset($appointment['start']) + 1,
            'height' => $height,
            'compact' => $height < self::COMPACT_HEIGHT,
            'time' => $range,
            'client' => $appointment['client'],
            'service' => $appointment['service'],
            'serviceDetail' => __(':service, :minutes min', ['service' => $appointment['service'], 'minutes' => $appointment['duration']]),
            'hairdresserName' => $hairdresser,
            'when' => __(':day, :range', ['day' => $this->date->isoFormat('dddd D MMMM'), 'range' => $range]),
        ];
    }

    /**
     * @return array{top: int, height: int, label: string}
     */
    private function offBlock(int $from, int $to, string $label): array
    {
        return ['top' => $this->offset($from), 'height' => $this->offset($to) - $this->offset($from), 'label' => $label];
    }

    private function offset(int $minutes): int
    {
        return (int) round(($minutes - GeneratedAgenda::OPENING) * self::PIXELS_PER_MINUTE);
    }

    private function time(int $minutes): string
    {
        return CarbonImmutable::createFromTime(intdiv($minutes, 60), $minutes % 60, 0, 'UTC')->isoFormat('LT');
    }
}
