<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class WorkingDayCalculator
{
    /**
     * Hitung jumlah hari kerja.
     * Sabtu & Minggu tidak dihitung.
     */
    public function calculate(
        Carbon|string $start,
        Carbon|string $end
    ): int {

        $start = $start instanceof Carbon
            ? $start->copy()->startOfDay()
            : Carbon::parse($start)->startOfDay();

        $end = $end instanceof Carbon
            ? $end->copy()->startOfDay()
            : Carbon::parse($end)->startOfDay();

        if ($start->greaterThan($end)) {
            return 0;
        }

        $days = 0;

        foreach (CarbonPeriod::create($start, $end) as $date) {

            if ($date->isWeekend()) {
                continue;
            }

            $days++;

        }

        return $days;
    }

    /**
     * Apakah tanggal merupakan hari kerja.
     */
    public function isWorkingDay(Carbon|string $date): bool
    {
        $date = $date instanceof Carbon
            ? $date
            : Carbon::parse($date);

        return ! $date->isWeekend();
    }

    /**
     * Ambil daftar tanggal kerja.
     */
    public function workingDates(
        Carbon|string $start,
        Carbon|string $end
    ): array {

        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        $dates = [];

        foreach (CarbonPeriod::create($start, $end) as $date) {

            if ($date->isWeekend()) {
                continue;
            }

            $dates[] = $date->toDateString();

        }

        return $dates;
    }
}