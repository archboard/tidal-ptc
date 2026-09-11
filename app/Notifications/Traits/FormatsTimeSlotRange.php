<?php

namespace App\Notifications\Traits;

use App\Models\User;
use Carbon\CarbonImmutable;

trait FormatsTimeSlotRange
{
    protected function formatRange(User $notifiable, CarbonImmutable $start, CarbonImmutable $end): string
    {
        $timeFormat = $notifiable->is_24h ? 'HH:mm' : 'h:mm A';
        $starts = $notifiable->dateFromApp($start);
        $ends = $notifiable->dateFromApp($end);

        return $starts->isoFormat('dddd, LL')
            .' '.$starts->isoFormat($timeFormat)
            .' – '.$ends->isoFormat($timeFormat)
            .' ('.$starts->tzName.')';
    }
}
