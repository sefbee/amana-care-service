<?php

namespace App\Admin\Services;

use Illuminate\Validation\ValidationException;

class WeeklySchedule
{
    private const WEEKDAYS = ['Sun' => 0, 'Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Fri' => 5, 'Sat' => 6];

    /** @return array<int,array{weekday:int,start_minute:int,end_minute:int}> */
    public function slots(array $schedule): array
    {
        $weekdays = $schedule['weekdays'] ?? [];
        $start = $schedule['start_time'] ?? null;
        $end = $schedule['end_time'] ?? null;
        if ($weekdays === [] || ! $start || ! $end) {
            throw ValidationException::withMessages(['schedule' => 'Service request schedule is incomplete.']);
        }

        $startMinute = $this->minuteOfDay($start);
        $endMinute = $this->minuteOfDay($end);
        if ($startMinute === $endMinute) {
            throw ValidationException::withMessages(['schedule' => 'Schedule start and end times must differ.']);
        }

        $slots = [];
        foreach (array_unique($weekdays) as $day) {
            if (! array_key_exists($day, self::WEEKDAYS)) {
                throw ValidationException::withMessages(['schedule' => 'Service request includes an invalid weekday.']);
            }
            $weekday = self::WEEKDAYS[$day];
            if ($endMinute > $startMinute) {
                $slots[] = ['weekday' => $weekday, 'start_minute' => $startMinute, 'end_minute' => $endMinute];
                continue;
            }

            $slots[] = ['weekday' => $weekday, 'start_minute' => $startMinute, 'end_minute' => 1440];
            if ($endMinute > 0) {
                $slots[] = ['weekday' => ($weekday + 1) % 7, 'start_minute' => 0, 'end_minute' => $endMinute];
            }
        }

        return $slots;
    }

    public function overlaps(array $first, array $second): bool
    {
        return (int) $first['weekday'] === (int) $second['weekday']
            && (int) $first['start_minute'] < (int) $second['end_minute']
            && (int) $second['start_minute'] < (int) $first['end_minute'];
    }

    private function minuteOfDay(string $time): int
    {
        if (! preg_match('/^(\d{2}):(\d{2})$/', $time, $matches)
            || (int) $matches[1] > 23 || (int) $matches[2] > 59) {
            throw ValidationException::withMessages(['schedule' => 'Service request contains an invalid time.']);
        }
        return ((int) $matches[1] * 60) + (int) $matches[2];
    }
}
